import './bootstrap';

window.addEventListener('trigger-zebra-print', event => {
    // 1. Force Secure Protocol and Port for thedsq.jeweltag.us
    if (typeof BrowserPrint !== 'undefined') {
        BrowserPrint.Configuration.PROTOCOL = "https";
        BrowserPrint.Configuration.PORT = 9101;
    }

    // 2. Catch the ZPL from Filament
    const zplData = event.detail.zpl || (event.detail[0] && event.detail[0].zpl);

    if (!zplData) return;

    // 3. Find and Send to Printer
    BrowserPrint.getDefaultDevice("printer", function(device) {
        if (device && device.connection !== undefined) {
            device.send(zplData, 
                function(success) { console.log("Printed!"); }, 
                function(error) { alert("Print Error: " + error); }
            );
        } else {
            alert("Printer not found. Please ensure Zebra Browser Print is running on your Mac and you have accepted the SSL certificate at https://localhost:9101/ssl_support");
        }
    }, function(error) {
        alert("Communication Error: Is the Zebra app running? Check https://localhost:9101/ssl_support");
    });
});