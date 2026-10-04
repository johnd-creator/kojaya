<?php

namespace App\Support;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

class DeploymentRuntimePermissions
{
    /** Establish runtime access without traversing private artifacts or symlinks. */
    public function apply(string $checkout, int $runtimeGroup): void
    {
        $root = realpath($checkout);
        if ($root === false || is_link($checkout) || PHP_OS_FAMILY === 'Windows') {
            throw new RuntimeException('A regular POSIX checkout is required.');
        }

        $environment = $root.'/.env';
        clearstatcache();
        $mode = @fileperms($environment);
        if (is_link($environment) || ! is_file($environment) || filegroup($environment) !== $runtimeGroup
            || $mode === false || ($mode & 0440) !== 0440 || ($mode & 07137) !== 0) {
            throw new RuntimeException('Serving environment security metadata is invalid.');
        }

        $process = proc_open(['git', '-C', $root, 'ls-files', '--stage', '-z'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if ($process === false) {
            throw new RuntimeException('Cannot enumerate tracked application files.');
        }
        $tracked = stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        if (proc_close($process) !== 0 || $tracked === false || $tracked === '') {
            throw new RuntimeException('Cannot enumerate tracked application files.');
        }

        $this->setPermissions($root, 0755);
        foreach (explode("\0", $tracked) as $entry) {
            if ($entry === '') {
                continue;
            }
            [$metadata, $relative] = explode("\t", $entry, 2);
            if (preg_match('#\A(?:app|bootstrap|config|database|lang|public|resources|routes|bin)/#', $relative) !== 1
                && preg_match('#\Adocs/user-guide/.+\.md\z#', $relative) !== 1
                && ! in_array($relative, ['artisan', 'composer.json', 'composer.lock'], true)) {
                continue;
            }
            if (str_starts_with($relative, 'bootstrap/cache/') || str_starts_with($relative, 'storage/')) {
                continue;
            }
            $path = $root.'/'.$relative;
            $this->assertRegularPath($root, $path);
            $directory = dirname($path);
            while ($directory !== $root) {
                $this->setPermissions($directory, 0755);
                $directory = dirname($directory);
            }
            $this->setPermissions($path, str_starts_with($metadata, '100755 ') ? 0755 : 0644);
        }

        foreach (['vendor', 'public/build'] as $relative) {
            $this->normalizeTree($root, $root.'/'.$relative, 0755, 0644);
        }

        foreach (['storage', 'storage/app', 'storage/app/private', 'storage/framework', 'storage/framework/cache'] as $relative) {
            $this->prepareWritableDirectory($root, $root.'/'.$relative, $runtimeGroup);
        }
        foreach (['storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs'] as $relative) {
            $path = $root.'/'.$relative;
            $this->prepareWritableDirectory($root, $path, $runtimeGroup);
            $this->normalizeTree($root, $path, 02770, 0660, $runtimeGroup, preserveRuntimeOwned: true);
        }
        $this->prepareWritableDirectory($root, $root.'/bootstrap/cache', $runtimeGroup);
        $this->normalizeTree($root, $root.'/bootstrap/cache', 02770, 0640, $runtimeGroup);
    }

    private function prepareWritableDirectory(string $root, string $path, int $group): void
    {
        if (! file_exists($path) && ! mkdir($path, 0700)) {
            throw new RuntimeException('Cannot prepare a runtime directory.');
        }
        $this->assertRegularPath($root, $path);
        if ($this->isRuntimeOwnedAndAccessible($path, $group, true)) {
            return;
        }
        $this->setPermissions($path, 02770, $group);
    }

    private function normalizeTree(string $root, string $path, int $directoryMode, int $fileMode, ?int $group = null, bool $preserveRuntimeOwned = false): void
    {
        if (! file_exists($path) && ! is_link($path)) {
            return;
        }
        $this->assertRegularPath($root, $path);
        if ($group === null) {
            $parent = dirname($path);
            while ($parent !== $root) {
                $this->setPermissions($parent, 0755);
                $parent = dirname($parent);
            }
        }
        if (! $preserveRuntimeOwned || $group === null || ! $this->isRuntimeOwnedAndAccessible($path, $group, true)) {
            $this->setPermissions($path, $directoryMode, $group);
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        /** @var SplFileInfo $entry */
        foreach ($iterator as $entry) {
            $this->assertRegularPath($root, $entry->getPathname());
            if ($preserveRuntimeOwned && $group !== null && $this->isRuntimeOwnedAndAccessible($entry->getPathname(), $group, $entry->isDir())) {
                continue;
            }
            $mode = $entry->isDir() ? $directoryMode : $fileMode;
            if (! $entry->isDir() && $fileMode === 0644 && ($entry->getPerms() & 0111) !== 0) {
                $mode = 0755;
            }
            $this->setPermissions($entry->getPathname(), $mode, $group);
        }
    }

    /** Existing runtime-owned state is verified, never chowned or widened by deployment. */
    private function isRuntimeOwnedAndAccessible(string $path, int $group, bool $directory): bool
    {
        if (! function_exists('posix_geteuid') || fileowner($path) === posix_geteuid()) {
            return false;
        }
        $owner = posix_getpwuid(fileowner($path));
        $mode = fileperms($path) & 07777;
        if ($owner === false || $owner['gid'] !== $group || filegroup($path) !== $group
            || ($mode & 0002) !== 0 || ($mode & ($directory ? 0770 : 0600)) !== ($directory ? 0770 : 0600)) {
            throw new RuntimeException('Existing runtime-owned state has unsafe ownership or access.');
        }

        return true;
    }

    private function assertRegularPath(string $root, string $path): void
    {
        $current = $path;
        while ($current !== $root) {
            if (! str_starts_with($current, $root.'/') || is_link($current)) {
                throw new RuntimeException('Runtime permission target contains a symlink or escapes the checkout.');
            }
            $current = dirname($current);
        }
        if (! is_file($path) && ! is_dir($path)) {
            throw new RuntimeException('Runtime permission target is not a regular file or directory.');
        }
    }

    private function setPermissions(string $path, int $mode, ?int $group = null): void
    {
        clearstatcache(true, $path);
        if ((fileperms($path) & 07777) === $mode && ($group === null || filegroup($path) === $group)) {
            return;
        }
        if (($group !== null && ! @chgrp($path, $group)) || ! @chmod($path, $mode)) {
            throw new RuntimeException('Cannot establish runtime filesystem permissions.');
        }
        clearstatcache(true, $path);
        if ((fileperms($path) & 07777) !== $mode || ($group !== null && filegroup($path) !== $group)) {
            throw new RuntimeException('Runtime filesystem permission verification failed.');
        }
    }
}
