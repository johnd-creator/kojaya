import { createInertiaApp } from "@inertiajs/vue3";
import createServer from "@inertiajs/vue3/server";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import type { DefineComponent } from "vue";
import { createSSRApp, h } from "vue";
import { renderToString } from "vue/server-renderer";
import { updateTheme } from "./composables/useAppearance";
import { registerAppSharedFeatures } from "./lib/appSetup";

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

createServer(
  (page) =>
    createInertiaApp({
      page,
      render: renderToString,
      title: (title) => (title ? `${title} - ${appName}` : appName),
      resolve: (name) =>
        resolvePageComponent(
          `./pages/${name}.vue`,
          import.meta.glob<DefineComponent>("./pages/**/*.vue"),
        ),
      setup: ({ App, props, plugin }) => {
        const vueApp = createSSRApp({ render: () => h(App, props) }).use(
          plugin,
        );

        registerAppSharedFeatures(vueApp);

        const appearance = props.initialPage.props.appearance;
        if (
          appearance === "dark" ||
          appearance === "light" ||
          appearance === "system"
        ) {
          updateTheme(appearance);
        }

        return vueApp;
      },
    }),
  { cluster: true },
);
