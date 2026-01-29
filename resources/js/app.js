import './bootstrap';

window.addEventListener('trigger-zebra-print', event => {
    // Filament v3 uses event.detail[0].zpl or event.detail.zpl depending on how it's dispatched
    // This check handles both
    const zplData = event.detail.zpl || (event.detail[0] && event.detail[0].zpl);

    if (!zplData) {
        console.error('No ZPL data found in event:', event.detail);
        alert('Error: No ZPL data received.');
        return;
    }

    // Check if the Zebra library is actually loaded on the page
    if (typeof BrowserPrint === 'undefined') {
        alert('Zebra BrowserPrint library not loaded. Please add the script tag to your layout.');
        return;
    }

    // Connect to the local Zebra Browser Print service
    BrowserPrint.getDefaultDevice("printer", function(device) {
        if (device && device.connection !== undefined) {
            // Send the ZPL directly to the printer via local USB/Network
            device.send(zplData, function(success) {
                console.log("Print Successful");
            }, function(error) {
                alert("Printing Error: " + error);
            });
        } else {
            alert("Printer not found. \n1. Ensure Zebra Browser Print app is running on your Mac. \n2. Visit https://localhost:9101/ssl_support and click 'Proceed' to trust the connection.");
        }
    }, function(error) {
        console.error("Browser Print Service Error:", error);
        alert("Cannot communicate with Zebra Browser Print app. Is it running?");
    });
});