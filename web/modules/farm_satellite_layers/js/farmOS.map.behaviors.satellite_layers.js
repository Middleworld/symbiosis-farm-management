(function (Drupal) {
    'use strict';

    /**
     * farmOS map behavior for adding satellite imagery layers.
     */
    farmOS.map.behaviors.satellite_layers = {
        attach: function (instance) {

            // Add Esri World Imagery Satellite layer
            const esriSatelliteLayer = instance.addLayer('xyz', {
                title: 'Satellite (Esri World Imagery)',
                url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
                attribution: 'Tiles &copy; <a href="https://www.esri.com/">Esri</a> &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
                base: true,
                visible: false,
                crossOrigin: 'anonymous'
            });

            // Add Google Satellite layer
            const googleSatelliteLayer = instance.addLayer('xyz', {
                title: 'Satellite (Google)',
                url: 'https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}',
                attribution: 'Imagery &copy; Google',
                base: true,
                visible: false,
                crossOrigin: 'anonymous'
            });

            // Add Google Hybrid layer (satellite with labels)
            const googleHybridLayer = instance.addLayer('xyz', {
                title: 'Satellite Hybrid (Google)',
                url: 'https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
                attribution: 'Imagery &copy; Google',
                base: true,
                visible: false,
                crossOrigin: 'anonymous'
            });

            // Add USGS Satellite layer (more reliable than Bing for now)
            const usgsSatelliteLayer = instance.addLayer('xyz', {
                title: 'Satellite (USGS)',
                url: 'https://basemap.nationalmap.gov/arcgis/rest/services/USGSImageryOnly/MapServer/tile/{z}/{y}/{x}',
                attribution: 'Tiles courtesy of the <a href="https://usgs.gov/">U.S. Geological Survey</a>',
                base: true,
                visible: false,
                crossOrigin: 'anonymous'
            });

            // Add OpenTopoMap for terrain satellite view
            const openTopoLayer = instance.addLayer('xyz', {
                title: 'Satellite Terrain (OpenTopo)',
                url: 'https://{a-c}.tile.opentopomap.org/{z}/{x}/{y}.png',
                attribution: 'Map data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, <a href="http://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a>',
                base: true,
                visible: false,
                crossOrigin: 'anonymous'
            });

            // Helper function to convert XYZ coordinates to QuadKey for Bing Maps
            function xyzToQuadKey(x, y, z) {
                let quadKey = '';
                for (let i = z; i > 0; i--) {
                    let digit = 0;
                    const mask = 1 << (i - 1);
                    if ((x & mask) !== 0) {
                        digit++;
                    }
                    if ((y & mask) !== 0) {
                        digit += 2;
                    }
                    quadKey += digit;
                }
                return quadKey;
            }

            // Add Bing Aerial layer with custom tile URL function
            const bingAerialLayer = instance.addLayer('xyz', {
                title: 'Satellite (Bing Aerial)',
                url: function (tileCoord) {
                    if (tileCoord) {
                        const z = tileCoord[0];
                        const x = tileCoord[1];
                        const y = tileCoord[2];
                        const quadKey = xyzToQuadKey(x, y, z);
                        return 'https://ecn.t0.tiles.virtualearth.net/tiles/a' + quadKey + '.jpeg?g=587&mkt=en-gb&n=z';
                    }
                    return '';
                },
                attribution: '&copy; <a href="https://www.microsoft.com/maps/">Microsoft Bing Maps</a>',
                base: true,
                visible: false,
                crossOrigin: 'anonymous'
            });

            // Log that satellite layers have been added
            console.log('farmOS Satellite Layers: Added satellite imagery options to map');

            // Store layer references on the instance for potential future use
            instance.satelliteLayers = {
                esri: esriSatelliteLayer,
                google: googleSatelliteLayer,
                googleHybrid: googleHybridLayer,
                bing: bingAerialLayer,
                usgs: usgsSatelliteLayer,
                openTopo: openTopoLayer
            };
        }
    };

}(Drupal));
