module.exports = () => {
  return {
    plugins: [
      require('postcss-import'),
      require('autoprefixer')({ grid: true }),
      require('postcss-combine-duplicated-selectors')({ removeDuplicatedProperties: true }),
      require('postcss-preset-env')({
        stage: 0,
        autoprefixer: { grid: true },
        features: {
          'nesting-rules': false,
        },
      }),
      require('cssnano')({
        preset: [
          'default',
          {
            discardComments: {
              removeAll: true,
            },
          },
        ],
      }),
      require('postcss-reporter')({
        clearReportedMessages: true,
      }),
      require('postcss-sort-media-queries'),
    ],
  };
};
