import path from 'node:path'; import { fileURLToPath } from 'node:url'; import { defineNexoPOSModuleConfig } from '../../resources/vite-nexopos-module.js';
const __dirname=path.dirname(fileURLToPath(import.meta.url)); export default defineNexoPOSModuleConfig({dirname:__dirname,inputs:['Resources/ts/launcher.ts','Resources/ts/settings.ts','Resources/css/style.css'],port:3357});
