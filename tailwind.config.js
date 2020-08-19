module.exports = {
    important: true,
    theme: {
      fontFamily: {
        display: ['Gilroy', 'sans-serif'],
        body: ['Graphik', 'sans-serif'],
      },
      extend: {
        colors: {
          cyan: '#9cdbff',
        },
        margin: {
          '96': '24rem',
          '128': '32rem',
        },
      }
    },
    future: {
      removeDeprecatedGapUtilities: true,
    },
    variants: {
      opacity: [ 'responsive', 'hover', 'active' ]
    }
  }