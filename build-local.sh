#!/bin/bash
# build local

rm -Rf ./wp-content/themes/brent/css/
rm -Rf ./wp-content/themes/brent/fonts/
rm -Rf ./wp-content/themes/brent/img/
rm -Rf ./wp-content/themes/brent/js/
rm ./manifest.json
rm ./precache-*
rm ./service-worker.js
rm ./robots.txt

chmod -R 755 ./*
cd ./brent-frontend
yarn install
yarn build-local
cp -R ./dist/wp-content/themes/brent/* ../wp-content/themes/brent/
cp -R ./dist/img ../wp-content/themes/brent/
cp ./dist/robots.txt ../
cp ./dist/manifest.json ../
cp ./dist/precache-* ../
cp ./dist/service-worker.js ../
cp ./dist/pdf.worker.min.js ../
