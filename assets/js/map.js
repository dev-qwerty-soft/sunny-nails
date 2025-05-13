import { Loader } from '@googlemaps/js-api-loader';

(async function () {
  const cont = document.querySelector('#map');
  if (!cont) return;
  const attr = cont.dataset?.center;
  const token = cont.dataset?.token;
  const cords = attr ? JSON.parse(attr) : { lat: 0, lng: 0 };
  const loader = new Loader({
    apiKey: token,
    version: 'weekly',
    libraries: ['maps'],
  });
  const { Map, Marker } = await loader.importLibrary('maps');
  const map = new Map(cont, {
    center: cords,
    zoomControl: false,
    streetViewControl: false,
    mapTypeControl: false,
    fullscreenControl: false,
    zoom: 14,
  });
  const marker = new Marker({
    position: cords,
    map: map,
  });
})();