# directus-clone-entities
Deep clone entities in Directus

DEPLOY
1. pull latest from master

2. copy custom public files into directus root

3. CD into /public/extensions/custom/pages/copy

4. npm install

5. run:
   parcel build page.vue -d ./ --no-source-maps --global __DirectusExtension__



VERSIONS
- node: 1.15.3
- directus: 7.11.0
- directus API: 2.6.0
