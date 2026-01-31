<template>
  <farm-main :paddingTop="['xl', 'xxl']" :paddingX="['m', 'xl', 'xxl']">
    <app-bar-options :title="$t('QR Scanner')"/>
    
    <div v-if="!scanning && !asset">
      <farm-text>Scan QR codes on farm assets (beds, plants, equipment) to view details and create logs.</farm-text>
      <br>
      <farm-button @click="startScanning" :fullWidth="true">
        Start Camera
      </farm-button>
    </div>

    <div v-if="scanning">
      <div id="qr-reader" style="width: 100%"></div>
      <br>
      <farm-button @click="stopScanning" :fullWidth="true" variant="secondary">
        Stop Camera
      </farm-button>
      <br><br>
      <farm-text v-if="lastScan" class="status-text">
        Last scan: {{ lastScan }}
      </farm-text>
    </div>

    <div v-if="asset && !scanning">
      <farm-card :title="asset.attributes.name">
        <template #header-right>
          <farm-button @click="reset" variant="secondary" size="small">
            Scan Another
          </farm-button>
        </template>
        
        <farm-stack :spacing="['s', 'm']">
          <div>
            <farm-text-label>Asset Type</farm-text-label>
            <farm-text>{{ asset.type }}</farm-text>
          </div>
          
          <div v-if="asset.attributes.status">
            <farm-text-label>Status</farm-text-label>
            <farm-chip :color="getStatusColor(asset.attributes.status)">
              {{ asset.attributes.status }}
            </farm-chip>
          </div>
          
          <div v-if="asset.attributes.id_tag && asset.attributes.id_tag.length > 0">
            <farm-text-label>ID Tags</farm-text-label>
            <farm-chip v-for="tag in asset.attributes.id_tag" :key="tag.id">
              {{ tag.id }}
            </farm-chip>
          </div>
          
          <div v-if="location">
            <farm-text-label>Location</farm-text-label>
            <farm-text>{{ location }}</farm-text>
          </div>
          
          <div v-if="asset.attributes.notes">
            <farm-text-label>Notes</farm-text-label>
            <farm-text>{{ asset.attributes.notes.value }}</farm-text>
          </div>
        </farm-stack>
      </farm-card>
      
      <br>
      <farm-button @click="viewAsset" :fullWidth="true">
        View Full Details
      </farm-button>
    </div>

    <div v-if="error">
      <br>
      <farm-card variant="error">
        <farm-text>{{ error }}</farm-text>
      </farm-card>
    </div>
  </farm-main>
</template>

<script>
import { Html5Qrcode } from 'html5-qrcode';

export default {
  name: 'QrScannerContainer',
  data() {
    return {
      scanning: false,
      qrScanner: null,
      lastScan: null,
      asset: null,
      location: null,
      error: null,
    };
  },
  methods: {
    async startScanning() {
      this.scanning = true;
      this.error = null;
      this.asset = null;
      
      try {
        this.qrScanner = new Html5Qrcode('qr-reader');
        
        await this.qrScanner.start(
          { facingMode: 'environment' },
          {
            fps: 10,
            qrbox: { width: 250, height: 250 },
          },
          this.onScanSuccess,
          this.onScanError,
        );
      } catch (err) {
        this.error = `Error starting camera: ${err.message}`;
        this.scanning = false;
      }
    },
    
    async stopScanning() {
      if (this.qrScanner) {
        try {
          await this.qrScanner.stop();
          this.qrScanner.clear();
        } catch (err) {
          console.error('Error stopping scanner:', err);
        }
      }
      this.scanning = false;
    },
    
    async onScanSuccess(decodedText) {
      this.lastScan = decodedText;
      await this.stopScanning();
      await this.lookupAsset(decodedText);
    },
    
    onScanError(error) {
      // Ignore scanning errors (happens constantly while searching)
    },
    
    async lookupAsset(qrCode) {
      this.error = null;
      
      try {
        // Search for asset by ID tag
        const response = await this.$store.state.farm.remote.request({
          url: '/api/asset?filter[id_tag][value]=' + encodeURIComponent(qrCode),
          method: 'GET',
        });
        
        if (response.data.data && response.data.data.length > 0) {
          this.asset = response.data.data[0];
          
          // Fetch location if available
          if (this.asset.relationships?.location?.data) {
            const locationIds = this.asset.relationships.location.data.map(loc => loc.id);
            const locationResponse = await this.$store.state.farm.remote.request({
              url: `/api/asset/${locationIds[0]}`,
              method: 'GET',
            });
            this.location = locationResponse.data.data.attributes.name;
          }
        } else {
          this.error = `No asset found with QR code: ${qrCode}`;
        }
      } catch (err) {
        this.error = `Error looking up asset: ${err.message}`;
      }
    },
    
    getStatusColor(status) {
      const colorMap = {
        active: 'success',
        inactive: 'gray',
        archived: 'gray',
        pending: 'warning',
      };
      return colorMap[status] || 'primary';
    },
    
    viewAsset() {
      // Navigate to asset detail page
      this.$router.push(`/asset/${this.asset.type}/${this.asset.id}`);
    },
    
    reset() {
      this.asset = null;
      this.location = null;
      this.error = null;
      this.lastScan = null;
    },
  },
  beforeUnmount() {
    this.stopScanning();
  },
};
</script>

<style scoped>
.status-text {
  text-align: center;
  font-style: italic;
}

#qr-reader {
  border: 2px solid #4CAF50;
  border-radius: 8px;
}
</style>
