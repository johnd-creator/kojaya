import type { VariantProps } from "class-variance-authority";
import { cva } from "class-variance-authority";

export { default as Button } from "./Button.vue";

export const buttonVariants = cva(
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all duration-200 active:translate-y-px disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive",
  {
    variants: {
      variant: {
        default:
          "bg-emerald-700 text-white shadow-sm shadow-emerald-950/15 hover:bg-emerald-800 hover:shadow-md hover:shadow-emerald-950/20 dark:bg-emerald-600 dark:text-white dark:shadow-black/20 dark:hover:bg-emerald-500",
        destructive:
          "bg-destructive text-white shadow-sm shadow-destructive/15 hover:bg-destructive/90 hover:shadow-md hover:shadow-destructive/20 focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40 dark:bg-destructive/60 dark:shadow-black/20",
        outline:
          "border border-zinc-300 bg-white/80 shadow-sm shadow-zinc-950/5 hover:bg-zinc-100 hover:text-zinc-950 hover:shadow-md hover:shadow-zinc-950/10 dark:border-zinc-700 dark:bg-zinc-950/40 dark:shadow-black/20 dark:hover:bg-zinc-800 dark:hover:text-zinc-50",
        secondary:
          "bg-zinc-800 text-white shadow-sm shadow-zinc-950/10 hover:bg-zinc-700 hover:shadow-md hover:shadow-zinc-950/15 dark:bg-zinc-200 dark:text-zinc-950 dark:shadow-black/10 dark:hover:bg-zinc-300",
        ghost:
          "hover:bg-zinc-100/90 hover:text-zinc-950 dark:hover:bg-zinc-800 dark:hover:text-zinc-50",
        link: "text-primary underline-offset-4 hover:underline",
      },
      size: {
        default: "h-9 px-4 py-2 has-[>svg]:px-3",
        sm: "h-8 rounded-md gap-1.5 px-3 has-[>svg]:px-2.5",
        lg: "h-10 rounded-md px-6 has-[>svg]:px-4",
        icon: "size-9",
        "icon-sm": "size-8",
        "icon-lg": "size-10",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  },
);
export type ButtonVariants = VariantProps<typeof buttonVariants>;
