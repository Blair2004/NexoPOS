export default function esmify(options) {
    const cache = new Map();
  
    const normalize = (id) => {
      if (cache.has(id)) {
        return cache.get(id);
      }
  
      const parts = id.split("/node_modules/");
      const dirPaths = parts[parts.length - 1].split("/");
  
      let n = `npm~${dirPaths[0]}`;
  
      if (dirPaths[0][0] == "@") {
        n = `npm~${dirPaths[0]}~${dirPaths[1]}}`;
      }
  
      cache.set(id, n);
  
      return n;
    };
  
    return {
      name: "rollup-plugin-multiple-vendors",
  
      outputOptions(o) {
        o.manualChunks = ( id, api ) => {
          if (id.includes("node_modules")) {
            return normalize(id);
          }
        };
        return o;
      },
    };
  }