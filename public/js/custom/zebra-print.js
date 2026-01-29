window.addEventListener('trigger-zebra-print', event => {
    // 1. Safe Check for BrowserPrint and Configuration
    if (typeof BrowserPrint !== 'undefined') {
        
        // Ensure Configuration object exists before setting properties
        if (!BrowserPrint.Configuration) {
            BrowserPrint.Configuration = {};
        }

        BrowserPrint.Configuration.PROTOCOL = "https";
        BrowserPrint.Configuration.PORT = 9101;
    } else {
        console.error("Zebra Library (BrowserPrint) not found.");
        return;
    }

    // 2. Extract ZPL Data
    const zplData = event.detail.zpl || (event.detail[0] && event.detail[0].zpl);
    if (!zplData) return;

    // 3. Find and Send to Printer
    BrowserPrint.getDefaultDevice("printer", function(device) {
        if (device && device.connection !== undefined) {
            device.send(zplData, 
                function(success) { console.log("Printed successfully"); }, 
                function(error) { alert("Print Error: " + error); }
            );
        } else {
            alert("Printer not found. Ensure Zebra App is running and you have accepted SSL at https://localhost:9101/ssl_support");
        }
    }, function(error) {
        alert("Communication Error: Check https://localhost:9101/ssl_support");
    });
});