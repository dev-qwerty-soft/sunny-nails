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
  const { Map } = await loader.importLibrary('maps');
  const { AdvancedMarkerElement } = await loader.importLibrary('marker');
  const map = new Map(cont, {
    center: cords,
    zoomControl: false,
    streetViewControl: false,
    mapTypeControl: false,
    fullscreenControl: false,
    mapId: 'ba5b19aef975eacea766912a',
    zoom: 16,
  });
  new AdvancedMarkerElement({
    position: cords,
    map: map,
  });
})();