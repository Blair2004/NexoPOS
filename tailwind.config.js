const heightDims = {
  '6/7-screen': '85.71vh',
  '5/7-screen': '71.42vh',
  '4/7-screen': '57.14vh',
  '3/7-screen': '42.85vh',
  '2/7-screen': '28.57vh',
  '1/7-screen': '14.28vh',
  '5/6-screen': '83.33vh',
  '4/6-screen': '66.66vh',
  '3/6-screen': '50vh',
  '2/6-screen': '33.33vh',
  '1/6-screen': '16.66vh',
  '4/5-screen': '80vh',
  '3/5-screen': '60vh',
  '2/5-screen': '40vh',
  '1/5-screen': '20vh',
  '3/4-screen': '75vh',
  '2/4-screen': '50vh',
  '1/4-screen': '25vh',
  '2/3-screen': '66.66vh',
  '1/3-screen': '33.33vh',
  'half': '50vh',
  '95vh': '95vh'
};

const widthDims = {
  '6/7-screen': '85.71vw',
  '5/7-screen': '71.42vw',
  '4/7-screen': '57.14vw',
  '3/7-screen': '42.85vw',
  '2/7-screen': '28.57vw',
  '1/7-screen': '14.28vw',
  '5/6-screen': '83.33vw',
  '4/6-screen': '66.66vw',
  '3/6-screen': '50vw',
  '2/6-screen': '33.33vw',
  '1/6-screen': '16.66vw',
  '4/5-screen': '80vw',
  '3/5-screen': '60vw',
  '2/5-screen': '40vw',
  '1/5-screen': '20vw',
  '3/4-screen': '75vw',
  '2/4-screen': '50vw',
  '1/4-screen': '25vw',
  '2/3-screen': '66.66vw',
  '1/3-screen': '33.33vw',
  'half': '50vw',
  '95vw': '95vw'
}

module.exports = {
  important: true,
  future: {
    removeDeprecatedGapUtilities: true,
    purgeLayersByDefault: true,
    defaultLineHeights: true,
    standardFontWeights: true,
  },
  theme: {
    fontFamily: {
      display: ['Gilroy', 'sans-serif'],
      body: ['Graphik', 'sans-serif'],
    },
    extend: {
      height: heightDims,
      minHeight: heightDims,
      width: {
          ...widthDims
      },
      spacing: {
          '72': '18rem',
          '84': '21rem',
          '96': '24rem',
          '108': '27rem',
          '120': '30rem',
      },
      inset: {
          '-5': '-2.5em',
          '-10': '-5em',
          '-20': '-10em',
          '-25': '-12.5em',
          '-30': '-15em',
          '-40': '-20em',
          '-50': '-25em',
          '-60': '-30em',
          '-70': '-35em',
          '0': '0em',
          '5': '2.5em',
          '10': '5em',
          '20': '10em',
          '25': '12.5em',
          '30': '15em',
          '40': '20em',
          '50': '25em',
          '60': '30em',
          '70': '35em',
          ...widthDims
      },
      margin: {
        '-5': '-2.5em',
        '96': '24rem',
        '128': '32rem',
      },
      colors: {
        cyan: '#9cdbff',
      },
    }
  },
  future: {
    removeDeprecatedGapUtilities: true,
  },
  variants: {
    opacity: [ 'responsive', 'hover', 'active' ]
  },
  experimental: {
    applyComplexClasses: true,
  },
}