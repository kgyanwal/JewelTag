import './bootstrap';

window.addEventListener('trigger-zebra-print', event => {
    // 1. Force BrowserPrint to use HTTPS and Port 9101 for your secure domain
    if (typeof BrowserPrint !== 'undefined') {
        BrowserPrint.Configuration.PROTOCOL = "https";
        BrowserPrint.Configuration.PORT = 9101;
    } else {
        alert('Zebra Library (BrowserPrint) is missing. Check AdminPanelProvider.');
        return;
    }

    // 2. Extract ZPL from Filament event
    const zplData = event.detail.zpl || (event.detail[0] && event.detail[0].zpl);

    if (!zplData) {
        console.error('No ZPL data received');
        return;
    }

    // 3. Try to find the printer
    BrowserPrint.getDefaultDevice("printer", function(device) {
        if (device && device.connection !== undefined) {
            // 4. Send the ZPL string
            device.send(zplData, function(success) {
                console.log("Print sent successfully to: " + device.name);
            }, function(error) {
                alert("Printer Error: " + error);
            });
        } else {
            // This usually means the SSL is accepted, but the "Accepted Hosts" popup was missed
            alert("Printer not found. Please look for the Zebra permission popup on your Mac screen and click YES.");
        }
    }, function(error) {
        // This usually means the browser is blocking localhost:9101
        console.error("Communication Error:", error);
        alert("Cannot communicate with Zebra App.\n\nFix:\n1. Open https://localhost:9101/ssl_support\n2. Click 'Advanced' -> 'Proceed to localhost'.\n3. Keep that tab open and try again.");
    });
});