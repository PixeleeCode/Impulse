import typescript from '@rollup/plugin-typescript';
import { terser } from 'rollup-plugin-terser';

export default {
  input: 'impulse/index.ts',
  output: {
    file: 'public/impulse.js',
    format: 'iife', // UMD ou iife si usage <script> global
    name: 'Impulse',
    sourcemap: true,
  },
  plugins: [
    typescript({ tsconfig: './tsconfig.json' }),
    terser() // minification
  ]
};
