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

function onPageLoad() {
    M.toast({html: 'Please check the correctness of your search query!'});
}
