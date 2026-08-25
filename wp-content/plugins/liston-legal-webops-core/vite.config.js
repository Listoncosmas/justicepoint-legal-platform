import { defineConfig } from 'vite';

export default defineConfig({
  build: {
    outDir: 'assets/dist',
    emptyOutDir: true,
    lib: { entry: 'assets/src/directory.js', formats: ['iife'], name: 'JusticePointDirectory', fileName: () => 'directory.js' },
    rollupOptions: { output: { assetFileNames: 'directory.[ext]' } },
    sourcemap: true,
  },
});

