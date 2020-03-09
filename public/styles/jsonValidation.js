function validateJsonBySubmit() {
    try {
        let textForValidation = document.getElementById("form_mongoEntity") ?
            document.getElementById("form_mongoEntity").value :
            document.getElementById("textarea").value;
        
        JSON.parse(textForValidation);
        if (textForValidation.includes("\"\":")) {
            event.preventDefault();
            M.toast({html: 'Invalid JSON structure: empty key found!'});
        }
    } catch (e) {
        event.preventDefault();
        M.toast({html: 'Invalid JSON structure!'});
    }
}

function validateConnectionString() {
        let connectionString = document.getElementById("connection_string").value;
        if (!connectionString.startsWith("mongodb+srv://") && !connectionString.startsWith("mongodb://")){
            event.preventDefault();
            M.toast({html: 'The connection string should starts with "mongodb+srv://" or "mongodb://"!'});
        }
}

function onPageLoad() {
    M.toast({html: 'Please check the correctness of your search query!'});
}
