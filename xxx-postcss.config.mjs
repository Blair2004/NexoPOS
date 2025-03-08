// postcss.config.js
module.exports = (ctx) => {
  return {
    // syntax: 'postcss-scss',
    // parser: 'postcss-scss',
    plugins: {
      // 'autoprefixer': {},
      // 'tailwindcss/nesting': require('tailwindcss/nesting'),
      'tailwindcss': require('tailwindcss'),      
      // 'postcss-hash': ctx.env === 'production' ? {
      //   algorithm: 'sha256',
      //     trim: 20,
      //     manifest: './public/css-manifest.json'
      // } : false,
      // 'cssnano': ctx.env === 'production' ? {} : false
    }
  };
}