// Get the page body ID
var thisPage = document.querySelector('body').id;


/** --------------------------------------------------------------------------------------------------------
 * MAIN PAGE
** -------------------------------------------------------------------------------------------------------*/
if (thisPage === 'list') {

    let request = new XMLHttpRequest();
    request.open('GET', dataURL); // This variable defined in the script tag of the template footer.
    request.responseType = 'json';
    request.send();
    request.onload = function() {

        // Any functions needing that JSON data must be in here.
        let storeData = request.response;

        // Set event handler on action option dropdown
        let contactNoteBox = document.getElementById('ctac_note');
        let actionOptions = document.querySelectorAll('.action-options');
        // Add an event listener to all action options lists.
        actionOptions.forEach(action => {
            action.addEventListener("change", list => {
                // This is the chosen value.
                let actionOption = list.target.value;
                // This is the messge to appear in the contact note box, from the data.json file.
                let contactNote = storeData.messages[actionOption] ? storeData.messages[actionOption] : '';
                // Send message through personalizer, in case it's an email
                contactNote = personalizer(contactNote);
                contactNoteBox.innerHTML = contactNote;
            });
        });

        // Set event handler on Promised Payment calendar
        let actionCalendar = document.querySelector('.action-calendar');
        actionCalendar.addEventListener("change", date => {            
            contactNoteBox.innerHTML = date.target.value;
        });
    }

    // Set active CSS on selected filters.
    // The #filters data tag in the HTML left column file is filled in with $_SESSION data by PHP
    let filters = document.querySelector('#filters').value.split('%');
    let aging = filters[0];
    let apay = filters[1];
    document.getElementById(aging).classList.add('active');
    document.getElementById(apay).classList.add('active');


    // Set event handler on action dropdown, displays additional action options.
    document.getElementById("ctac_action").onchange = function () {
        // Clear out text box
        document.getElementById('ctac_note').innerHTML = '';
        // Get the selected action value
        var val = this.options[this.selectedIndex].value;
        // Get all elements in the right account column (class="action-options")
        var allActionOptions = document.querySelectorAll('.action-options');        
        // For each one, toggle display depending upon their ID vs the selected action
        allActionOptions.forEach(el => {
            el.style.display = (el.id == val) ? "block" : "none";
        });

    };

    // Get the last-stocked timestamp, and the current time    
    var lastStocked = document.querySelector('#laststocked').value;
    var timeNow = Math.round((new Date()).getTime() / 1000);

    // Flash the pond-stock link if pond hasn't been stocked in 'hourLimit' hours
    var hourLimit = 12; 
    if (((timeNow - lastStocked) / 3600) > hourLimit) {
        var fish = "./img/stock-white.svg";
        setInterval(function() {
            document.querySelector('#stock-link-img').src = fish;
            fish = (fish === "./img/stock-white.svg") ? "./img/stock-red.svg" : "./img/stock-white.svg";
        }, 250);
    }

    // Show the alert if pond hasn't been stocked in 'tooLong' hours
    var tooLong = 40; 
    if (((timeNow - lastStocked) / 3600) > tooLong) {
        document.getElementById('stock-pond-warning').style.display = 'block';
    }

    // Personalizes the message replacing data placeholders with data from the DOM
    function personalizer(message) {
        // Get the data from the data tag in the footer.
        // This tag is generated by PHP.
        let thisCustomer = JSON.parse(document.getElementById('customer-data').value);
        // Execute placeholder substitutions.
        Object.keys(thisCustomer).forEach(el => {
            let placeholder = '%' + el + '%';
            placeholder = new RegExp(placeholder, 'g');
            message = message.replace(placeholder, thisCustomer[el]);
        });
        return message;
    }


/** --------------------------------------------------------------------------------------------------------
 * EMAILER PAGE
** -------------------------------------------------------------------------------------------------------*/
} else if (thisPage === "emailer") {

    // For when the emails are sending ...
    document.getElementById('emailer-submit').addEventListener('click', () => {
        setTimeout(function() {
            document.getElementById('emailer-submit').value = 'Sit tight. This may take a while.';
        }, 200);
    });


/** --------------------------------------------------------------------------------------------------------
 * STOCK PAGE
** -------------------------------------------------------------------------------------------------------*/
} else if (thisPage === 'stock') {

    let payments = document.querySelectorAll('.payment-delete');

    // If there are no payments, say so.
    if (payments.length == 0) {
        document.getElementById('no-more-payments').style.display = 'block';
    } else {
        // Set a counter
        let i = 0;
        // Add an event listener to all payment items.
        payments.forEach(payment => {
            payment.addEventListener('click', click => {
                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function() {
                    if (xhttp.readyState == XMLHttpRequest.DONE) {   // XMLHttpRequest.DONE == 4
                        if (xhttp.status == 200) {
                            // The request went through.
                        }
                        else if (xhttp.status == 400) {
                            alert('There was an error 400');
                        }
                        else {
                            alert('something else other than 200 was returned');
                        }
                    }
                };
                xhttp.open('GET', url + 'stock/paymentSeen/' + click.target.id);
                xhttp.send();
                click.target.parentNode.style.display = 'none';
                i++;
                // If the counter equals the number of payments, display the "no more payments" div.
                if (i == payments.length) {
                    document.getElementById('no-more-payments').style.display = 'block';
                }
            });
        });
    }   

    document.getElementById('get-aimsi-instructions').addEventListener('click', function()  {
        document.getElementById('aimsi-instructions').style.display = 'block';
    });

}