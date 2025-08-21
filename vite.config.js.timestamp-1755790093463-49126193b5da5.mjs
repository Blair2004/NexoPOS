// vite.config.js
import { defineConfig, loadEnv } from "file:///var/www/html/default-v6/node_modules/vite/dist/node/index.js";
import laravel from "file:///var/www/html/default-v6/node_modules/laravel-vite-plugin/dist/index.js";
import mkcert from "file:///var/www/html/default-v6/node_modules/vite-plugin-mkcert/dist/mkcert.mjs";
import { resolve } from "path";
import vuePlugin from "file:///var/www/html/default-v6/node_modules/@vitejs/plugin-vue/dist/index.mjs";
import tailwindcss from "file:///var/www/html/default-v6/node_modules/@tailwindcss/vite/dist/index.mjs";
var __vite_injected_original_dirname = "/var/www/html/default-v6";
var vite_config_default = ({ mode }) => {
  process.env = { ...process.env, ...loadEnv(mode, process.cwd()) };
  return defineConfig({
    base: "./",
    server: {
      port: 3331,
      host: "127.0.0.1",
      hmr: {
        protocol: "wss",
        host: "localhost"
      },
      https: true
    },
    resolve: {
      alias: [
        {
          find: "&",
          replacement: resolve(__vite_injected_original_dirname, "resources")
        },
        {
          find: "~",
          replacement: resolve(__vite_injected_original_dirname, "resources/ts")
        }
      ]
    },
    plugins: [
      tailwindcss(),
      laravel({
        input: [
          "resources/ts/bootstrap.ts",
          "resources/ts/app.ts",
          "resources/ts/auth.ts",
          "resources/ts/pos.ts",
          "resources/ts/pos-init.ts",
          "resources/ts/setup.ts",
          "resources/ts/update.ts",
          "resources/ts/cashier.ts",
          "resources/ts/lang-loader.ts",
          "resources/ts/dev.ts",
          "resources/ts/popups.ts",
          "resources/ts/widgets.ts",
          "resources/ts/wizard.ts",
          "resources/css/app.css",
          "resources/css/grid.css",
          "resources/css/animations.css",
          "resources/css/fonts.css",
          "resources/scss/line-awesome/1.3.0/scss/line-awesome.scss",
          // themes
          "resources/css/light.css",
          "resources/css/dark.css",
          "resources/css/phosphor.css"
        ],
        refresh: true
      }),
      mkcert(),
      vuePlugin({
        template: {
          transformAssetUrls: {
            base: null,
            includeAbsolute: false
          }
        }
      })
    ]
  });
};
export {
  vite_config_default as default
};
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsidml0ZS5jb25maWcuanMiXSwKICAic291cmNlc0NvbnRlbnQiOiBbImNvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9kaXJuYW1lID0gXCIvdmFyL3d3dy9odG1sL2RlZmF1bHQtdjZcIjtjb25zdCBfX3ZpdGVfaW5qZWN0ZWRfb3JpZ2luYWxfZmlsZW5hbWUgPSBcIi92YXIvd3d3L2h0bWwvZGVmYXVsdC12Ni92aXRlLmNvbmZpZy5qc1wiO2NvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9pbXBvcnRfbWV0YV91cmwgPSBcImZpbGU6Ly8vdmFyL3d3dy9odG1sL2RlZmF1bHQtdjYvdml0ZS5jb25maWcuanNcIjtpbXBvcnQgeyBkZWZpbmVDb25maWcsIGxvYWRFbnYgfSBmcm9tICd2aXRlJztcblxuLy8gaW1wb3J0IGZzIGZyb20gJ2ZzJztcbmltcG9ydCBsYXJhdmVsIGZyb20gJ2xhcmF2ZWwtdml0ZS1wbHVnaW4nO1xuaW1wb3J0IG1rY2VydCBmcm9tICd2aXRlLXBsdWdpbi1ta2NlcnQnO1xuaW1wb3J0IHsgcmVzb2x2ZSB9IGZyb20gJ3BhdGgnO1xuLy8gaW1wb3J0IHBhdGggZnJvbSAncGF0aCc7XG5pbXBvcnQgdnVlUGx1Z2luIGZyb20gJ0B2aXRlanMvcGx1Z2luLXZ1ZSc7XG5pbXBvcnQgdGFpbHdpbmRjc3MgZnJvbSBcIkB0YWlsd2luZGNzcy92aXRlXCI7XG5cblxuZXhwb3J0IGRlZmF1bHQgKHsgbW9kZSB9KSA9PiB7XG4gICAgcHJvY2Vzcy5lbnYgPSB7Li4ucHJvY2Vzcy5lbnYsIC4uLmxvYWRFbnYobW9kZSwgcHJvY2Vzcy5jd2QoKSl9O1xuXG4gICAgcmV0dXJuIGRlZmluZUNvbmZpZyh7XG4gICAgICAgIGJhc2U6ICcuLycsXG4gICAgICAgIHNlcnZlcjoge1xuICAgICAgICAgICAgcG9ydDogMzMzMSxcbiAgICAgICAgICAgIGhvc3Q6ICcxMjcuMC4wLjEnLFxuICAgICAgICAgICAgaG1yOiB7XG4gICAgICAgICAgICAgICAgcHJvdG9jb2w6ICd3c3MnLFxuICAgICAgICAgICAgICAgIGhvc3Q6ICdsb2NhbGhvc3QnLFxuICAgICAgICAgICAgfSxcbiAgICAgICAgICAgIGh0dHBzOiB0cnVlLFxuICAgICAgICB9LFxuICAgICAgICByZXNvbHZlOiB7XG4gICAgICAgICAgICBhbGlhczogW1xuICAgICAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgICAgICAgZmluZDogJyYnLFxuICAgICAgICAgICAgICAgICAgICByZXBsYWNlbWVudDogcmVzb2x2ZSggX19kaXJuYW1lLCAncmVzb3VyY2VzJyApLFxuICAgICAgICAgICAgICAgIH0sIHtcbiAgICAgICAgICAgICAgICAgICAgZmluZDogJ34nLFxuICAgICAgICAgICAgICAgICAgICByZXBsYWNlbWVudDogcmVzb2x2ZSggX19kaXJuYW1lLCAncmVzb3VyY2VzL3RzJyApLFxuICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICBdXG4gICAgICAgIH0sXG4gICAgICAgIHBsdWdpbnM6IFtcbiAgICAgICAgICAgIHRhaWx3aW5kY3NzKCksXG4gICAgICAgICAgICBsYXJhdmVsKHtcbiAgICAgICAgICAgICAgICBpbnB1dDogW1xuICAgICAgICAgICAgICAgICAgICAncmVzb3VyY2VzL3RzL2Jvb3RzdHJhcC50cycsXG4gICAgICAgICAgICAgICAgICAgICdyZXNvdXJjZXMvdHMvYXBwLnRzJyxcbiAgICAgICAgICAgICAgICAgICAgJ3Jlc291cmNlcy90cy9hdXRoLnRzJyxcbiAgICAgICAgICAgICAgICAgICAgJ3Jlc291cmNlcy90cy9wb3MudHMnLFxuICAgICAgICAgICAgICAgICAgICAncmVzb3VyY2VzL3RzL3Bvcy1pbml0LnRzJyxcbiAgICAgICAgICAgICAgICAgICAgJ3Jlc291cmNlcy90cy9zZXR1cC50cycsXG4gICAgICAgICAgICAgICAgICAgICdyZXNvdXJjZXMvdHMvdXBkYXRlLnRzJyxcbiAgICAgICAgICAgICAgICAgICAgJ3Jlc291cmNlcy90cy9jYXNoaWVyLnRzJyxcbiAgICAgICAgICAgICAgICAgICAgJ3Jlc291cmNlcy90cy9sYW5nLWxvYWRlci50cycsXG4gICAgICAgICAgICAgICAgICAgICdyZXNvdXJjZXMvdHMvZGV2LnRzJyxcbiAgICAgICAgICAgICAgICAgICAgJ3Jlc291cmNlcy90cy9wb3B1cHMudHMnLFxuICAgICAgICAgICAgICAgICAgICAncmVzb3VyY2VzL3RzL3dpZGdldHMudHMnLFxuICAgICAgICAgICAgICAgICAgICAncmVzb3VyY2VzL3RzL3dpemFyZC50cycsXG4gICAgXG4gICAgICAgICAgICAgICAgICAgICdyZXNvdXJjZXMvY3NzL2FwcC5jc3MnLFxuICAgICAgICAgICAgICAgICAgICAncmVzb3VyY2VzL2Nzcy9ncmlkLmNzcycsXG4gICAgICAgICAgICAgICAgICAgICdyZXNvdXJjZXMvY3NzL2FuaW1hdGlvbnMuY3NzJyxcbiAgICAgICAgICAgICAgICAgICAgJ3Jlc291cmNlcy9jc3MvZm9udHMuY3NzJyxcbiAgICAgICAgICAgICAgICAgICAgJ3Jlc291cmNlcy9zY3NzL2xpbmUtYXdlc29tZS8xLjMuMC9zY3NzL2xpbmUtYXdlc29tZS5zY3NzJyxcblxuICAgICAgICAgICAgICAgICAgICAvLyB0aGVtZXNcbiAgICAgICAgICAgICAgICAgICAgJ3Jlc291cmNlcy9jc3MvbGlnaHQuY3NzJyxcbiAgICAgICAgICAgICAgICAgICAgJ3Jlc291cmNlcy9jc3MvZGFyay5jc3MnLFxuICAgICAgICAgICAgICAgICAgICAncmVzb3VyY2VzL2Nzcy9waG9zcGhvci5jc3MnLFxuICAgICAgICAgICAgICAgIF0sXG4gICAgICAgICAgICAgICAgcmVmcmVzaDogdHJ1ZSxcbiAgICAgICAgICAgIH0pLFxuICAgICAgICAgICAgbWtjZXJ0KCksXG4gICAgICAgICAgICB2dWVQbHVnaW4oe1xuICAgICAgICAgICAgICAgIHRlbXBsYXRlOiB7XG4gICAgICAgICAgICAgICAgICAgIHRyYW5zZm9ybUFzc2V0VXJsczoge1xuICAgICAgICAgICAgICAgICAgICAgICAgYmFzZTogbnVsbCxcbiAgICAgICAgICAgICAgICAgICAgICAgIGluY2x1ZGVBYnNvbHV0ZTogZmFsc2UsXG4gICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgIH0pLFxuICAgICAgICBdLFxuICAgIH0pO1xufSJdLAogICJtYXBwaW5ncyI6ICI7QUFBMFAsU0FBUyxjQUFjLGVBQWU7QUFHaFMsT0FBTyxhQUFhO0FBQ3BCLE9BQU8sWUFBWTtBQUNuQixTQUFTLGVBQWU7QUFFeEIsT0FBTyxlQUFlO0FBQ3RCLE9BQU8saUJBQWlCO0FBUnhCLElBQU0sbUNBQW1DO0FBV3pDLElBQU8sc0JBQVEsQ0FBQyxFQUFFLEtBQUssTUFBTTtBQUN6QixVQUFRLE1BQU0sRUFBQyxHQUFHLFFBQVEsS0FBSyxHQUFHLFFBQVEsTUFBTSxRQUFRLElBQUksQ0FBQyxFQUFDO0FBRTlELFNBQU8sYUFBYTtBQUFBLElBQ2hCLE1BQU07QUFBQSxJQUNOLFFBQVE7QUFBQSxNQUNKLE1BQU07QUFBQSxNQUNOLE1BQU07QUFBQSxNQUNOLEtBQUs7QUFBQSxRQUNELFVBQVU7QUFBQSxRQUNWLE1BQU07QUFBQSxNQUNWO0FBQUEsTUFDQSxPQUFPO0FBQUEsSUFDWDtBQUFBLElBQ0EsU0FBUztBQUFBLE1BQ0wsT0FBTztBQUFBLFFBQ0g7QUFBQSxVQUNJLE1BQU07QUFBQSxVQUNOLGFBQWEsUUFBUyxrQ0FBVyxXQUFZO0FBQUEsUUFDakQ7QUFBQSxRQUFHO0FBQUEsVUFDQyxNQUFNO0FBQUEsVUFDTixhQUFhLFFBQVMsa0NBQVcsY0FBZTtBQUFBLFFBQ3BEO0FBQUEsTUFDSjtBQUFBLElBQ0o7QUFBQSxJQUNBLFNBQVM7QUFBQSxNQUNMLFlBQVk7QUFBQSxNQUNaLFFBQVE7QUFBQSxRQUNKLE9BQU87QUFBQSxVQUNIO0FBQUEsVUFDQTtBQUFBLFVBQ0E7QUFBQSxVQUNBO0FBQUEsVUFDQTtBQUFBLFVBQ0E7QUFBQSxVQUNBO0FBQUEsVUFDQTtBQUFBLFVBQ0E7QUFBQSxVQUNBO0FBQUEsVUFDQTtBQUFBLFVBQ0E7QUFBQSxVQUNBO0FBQUEsVUFFQTtBQUFBLFVBQ0E7QUFBQSxVQUNBO0FBQUEsVUFDQTtBQUFBLFVBQ0E7QUFBQTtBQUFBLFVBR0E7QUFBQSxVQUNBO0FBQUEsVUFDQTtBQUFBLFFBQ0o7QUFBQSxRQUNBLFNBQVM7QUFBQSxNQUNiLENBQUM7QUFBQSxNQUNELE9BQU87QUFBQSxNQUNQLFVBQVU7QUFBQSxRQUNOLFVBQVU7QUFBQSxVQUNOLG9CQUFvQjtBQUFBLFlBQ2hCLE1BQU07QUFBQSxZQUNOLGlCQUFpQjtBQUFBLFVBQ3JCO0FBQUEsUUFDSjtBQUFBLE1BQ0osQ0FBQztBQUFBLElBQ0w7QUFBQSxFQUNKLENBQUM7QUFDTDsiLAogICJuYW1lcyI6IFtdCn0K
