import { defineConfig } from 'vite';

export default defineConfig({
  build: {
    outDir: 'assets/dist',
    emptyOutDir: true,
    lib: { entry: 'assets/js/theme.js', formats: ['iife'], name: 'JusticePointTheme', fileName: () => 'theme.js' },
    sourcemap: true,
  },
});

