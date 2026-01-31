<template>
  <farm-stack space="m">
    <farm-text weight="bold" size="l">QR Code Scanner</farm-text>
    
    <div v-if="!scanning">
      <farm-btn @click="startScanning">
        Start Scanning
      </farm-btn>
    </div>

    <div v-if="scanning">
      <div id="qr-reader" style="width: 100%; max-width: 500px;"></div>
      <farm-btn @click="stopScanning" color="secondary">
        Stop Scanning
      </farm-btn>
    </div>

    <farm-card v-if="lastScannedCode">
      <farm-text weight="bold">Last Scanned Code:</farm-text>
      <farm-text>{{ lastScannedCode }}</farm-text>
    </farm-card>
  </farm-stack>
</template>

<script>
import { ref, onBeforeUnmount } from 'vue';
import { Html5Qrcode } from 'html5-qrcode';

export default {
  name: 'QrScannerWidget',
  setup() {
    const scanning = ref(false);
    const lastScannedCode = ref('');
    let html5QrCode = null;

    const startScanning = async () => {
      try {
        html5QrCode = new Html5Qrcode("qr-reader");
        
        await html5QrCode.start(
          { facingMode: "environment" },
          {
            fps: 10,
            qrbox: { width: 250, height: 250 }
          },
          onScanSuccess
        );
        
        scanning.value = true;
      } catch (err) {
        console.error('Failed to start scanning:', err);
        alert('Failed to start camera. Please allow camera access.');
      }
    };

    const stopScanning = async () => {
      if (html5QrCode) {
        try {
          await html5QrCode.stop();
          html5QrCode.clear();
          scanning.value = false;
        } catch (err) {
          console.error('Failed to stop scanning:', err);
        }
      }
    };

    const onScanSuccess = (decodedText) => {
      lastScannedCode.value = decodedText;
      console.log('Scanned:', decodedText);
      // TODO: Look up asset by this code
    };

    onBeforeUnmount(() => {
      if (scanning.value) {
        stopScanning();
      }
    });

    return {
      scanning,
      lastScannedCode,
      startScanning,
      stopScanning,
    };
  },
};
</script>

<style scoped>
#qr-reader {
  margin: 1rem 0;
}
</style>
