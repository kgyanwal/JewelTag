// REPLACE the entire file with:
window.addEventListener('trigger-zebra-print', event => {
    const zplData = event.detail.zpl || (event.detail[0] && event.detail[0].zpl);
    if (!zplData) {
        console.error('No ZPL data received');
        return;
    }

    if (typeof BrowserPrint === 'undefined') {
        alert('Zebra BrowserPrint library not loaded. Contact support.');
        return;
    }

    BrowserPrint.getDefaultDevice("printer", 
        function(device) {
            if (!device || device.connection === undefined) {
                alert(
                    'No printer found.\n\n' +
                    '1. Make sure Zebra BrowserPrint app is installed and running\n' +
                    '2. Open https://localhost:9101/ssl_support and accept the certificate\n' +
                    '3. Refresh this page and try again'
                );
                return;
            }
            device.send(
                zplData,
                function() { console.log('✅ Label printed successfully'); },
                function(error) { alert('Print Error: ' + error); }
            );
        },
        function(error) {
            alert(
                'Cannot connect to Zebra BrowserPrint app.\n\n' +
                'Steps to fix:\n' +
                '1. Download and install BrowserPrint from zebra.com\n' +
                '2. Open https://localhost:9101/ssl_support in this browser\n' +
                '3. Click Advanced → Proceed\n' +
                '4. Refresh and try again\n\n' +
                'Error: ' + error
            );
        }
    );
});