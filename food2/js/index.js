// Event listeners
document.addEventListener('submit', function (event) {
    event.preventDefault();

    var form = event.target;

    if (form.id === 'loginForm') {
        validateLogin(event);
    } else if (form.id === 'registerForm') {
        validateRegister(event);
    } else if (form.id === 'searchForm') {
        findCustomer();
    }
});

window.addEventListener('DOMContentLoaded', function () {
    let pathname = window.location.pathname;

    if (!pathname.includes('login.html') || !pathname.includes('register.html')) {
        checkLoggedIn().then(user => {
            if (user) {
                addUserNameToNavbar(user);
            }
        });
    }

    if (pathname.includes('landingpage.html')) {
        fetchData('food', 'menu');
        fetchPortfolio();
    } else if (pathname.includes('admin.html')) {
        checkStaffRole();
        checkRole();
        fetchData('fetch_food', 'foodTable', true);
        fetchStaff();
        fetchData('fetch_customers', 'userTable', true);
        fetchFeedback();
    } else if (pathname.includes('index.html')) {
        fetchData('food', 'menu');
        fetchPortfolio();
        checkRole();
        profileForm();
    } else if (pathname.includes('viewcart.html')) {
        fetchData('view_cart', 'carts');
        enableCheckoutButton();
    } else if (pathname.includes('payment.html')) {
        //Hanafi:
        fetchData('time', 'time');
        fetchData('estimatedTime', 'estimatedTime');
        fetchData('total', 'total');
        clearCustomerOrder();

        // Select the input elements
        let cardNumber = document.querySelector('.form-control');
        let expiryDate = document.querySelector('.form-control2');
        let cvc = document.querySelector('.form-control3');

        // Add event listeners
        cardNumber.addEventListener('input', formatCardNumber);
        expiryDate.addEventListener('input', formatExpiryDate);
        cvc.addEventListener('input', formatCVC);
    }

    const navbar = document.getElementById('mainNav');
    if (navbar) {
        checkLoggedIn();
    }
});

function findCustomer() {
    var email = document.getElementById('email').value;
    $.ajax({
        url: 'main.php',
        type: 'POST',
        data: {
            action: 'find_customer',
            email: email
        },
        success: function (data) {
            var customer = JSON.parse(data);

            if (customer.error) {
                alert(customer.error);
                return;
            }

            var table = document.getElementById('customerTableBody');
            table.innerHTML = '';
            var row = document.createElement('tr');
            var name = document.createElement('td');
            name.textContent = customer.custName;
            var email = document.createElement('td');
            email.textContent = customer.email;
            var phone = document.createElement('td');
            phone.textContent = customer.custPhoneNo;
            row.appendChild(name);
            row.appendChild(email);
            row.appendChild(phone);
            table.appendChild(row);
        }
    });
}

function fetchPortfolio() {
    $.ajax({
        url: 'main.php',
        type: 'POST',
        data: {
            action: 'portfolio'
        },
        success: function (data) {
            var foods = data;
            var isLandingPage = window.location.pathname.endsWith('landingpage.html');
            foods.forEach(function (food) {
                var foodID = food.id;
                var custID = food.custID;
                var html = `
                <div class="portfolio-modal modal fade" id="portfolioModal${food.id}" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="close-modal" data-bs-dismiss="modal"><img src="assets/img/close-icon.svg" alt="Close modal" /></div>
                            <div class="container">
                                <div class="row justify-content-center">
                                    <div class="col-lg-8">
                                        <div class="modal-body">
                                            <h2 class="text-uppercase">${food.title}</h2>
                                            <p class="item-intro text-muted">${food.subtitle}</p>
                                            <img class="img-fluid portfolio-img d-block mx-auto" src="${food.img_src}" alt="..." />
                                            <p>${food.extra_desc}</p>
                                            <ul class="list-inline">
                                                <li><strong>Price:</strong> RM ${food.price}</li>
                                                <li><strong>Quantity:</strong> ${food.quantity}</li>   
                                            </ul>
                                            `;
                if (!isLandingPage) {
                    html += `
                    <div class="d-flex justify-content-center">
                        <div class="input-group" style="width: 250px;">
                            <span class="input-group-text">Quantity</span>
                            <button class="btn btn-outline-primary" type="button" onclick="decrementQuantity(${foodID})">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="text" class="form-control text-center" id="quantity-${foodID}" value="1" readonly>
                            <button class="btn btn-outline-primary" type="button" onclick="incrementQuantity(${foodID}, ${food.quantity})">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="d-flex justify-content-center mt-3">
                        <button class="btn btn-primary btn-xl text-uppercase mr-3" data-bs-dismiss="modal" type="button" onclick="addToCart(${custID}, ${foodID}, document.getElementById('quantity-${foodID}').value)">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>`;
                }
                html += `
                    <button class="btn btn-danger btn-xl text-uppercase" data-bs-dismiss="modal" type="button">
                        <i class="fas fa-xmark me-1"></i> Back to Menu
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</div>`;
                // Append the HTML to a container element on your page
                $('#autoportfolio').append(html);
            });
        },
        error: function (error) {
            console.log(error);
        }
    });
}



function addToCart(custID, foodID, quantity) {
    $.ajax({
        url: 'main.php', // replace with your PHP file
        type: 'post',
        data: {
            action: 'add_to_cart',
            custID: custID,
            foodID: foodID,
            quantity: quantity
        }
    });
}

function addUserNameToNavbar(user) {
    let userName = user.staffName || user.custName || user.email;
    const charLimit = 6;
    if (userName.length > charLimit) {
        userName = userName.substring(0, charLimit) + '...';
    }

    const navItem = document.createElement('li');
    navItem.classList.add('nav-item');
    navItem.innerHTML = `<a class="nav-link" href="#profileModal" data-bs-toggle="modal">${userName}</a>`;
    document.querySelector('.navbar-nav').appendChild(navItem);
    profileForm(user);
}


function cancelPayment() {
    window.location.href = "viewcart.html";
}

function checkLoggedIn() {
    return fetch('main.php?session_data=true') // Ensure we return the fetch promise
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // If the user is not logged in, redirect to the login page
            // not included registration page
            if (!data.user && !window.location.pathname.includes('register.html') && !window.location.pathname.includes('login.html') && !window.location.pathname.includes('landingpage.html')) {
                redirectToLandingPage();
            } else {
                return data.user; // Return the user data for chaining
            }
        })
        .catch(error => {
            console.error('There has been a problem with your fetch operation:', error);
        });
}

function checkRole() {
    $.ajax({
        url: 'main.php',
        type: 'POST',
        data: {
            action: 'check_role'
        },
        success: function (response) {
            if (response.trim() === 'staff' && !window.location.pathname.includes('admin.html')) {
                $('.navbar-nav').append('<li class="nav-item"><a class="nav-link" href="admin.html">Admin Dashboard</a></li>');
            }
        }
    });
}

function checkStaffRole() {
    fetch('main.php?session_data=true')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (!data.user || !data.user.staffID) {
                redirectToLogin();
            }
        })
        .catch(error => {
            console.error('There has been a problem with your fetch operation:', error);
        });
}

function checkStaffOrCustomer() {
    fetch('main.php?session_data=true')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.user) {
                return data.user.staffID ? 'staff' : 'customer';
            }
        })
        .catch(error => {
            console.error('There has been a problem with your fetch operation:', error);
        });
}


function collectData(row, value) {
    var updatedData = {};
    row.querySelectorAll('input').forEach(function (input, index) {
        updatedData[Object.keys(value)[index]] = input.value;
    });
    return updatedData;
}

function createActionCell(row, value) {
    var actionCell = document.createElement('td');
    var editButton = createButton('Edit', 'btn-primary', function () {
        enableEditing(row);
    });
    var saveButton = createButton('Save', 'btn-success', function () {
        var updatedData = collectData(row, value);
        sendUpdatedData(updatedData);
    });

    // delete button
    var deleteButton = createButton('Delete', 'btn-danger', function () {
        var confirmDelete = confirm('Are you sure you want to delete this item?');
        if (!confirmDelete) {
            return;
        }
        var data = collectData(row, value);
        deleteData(data);
    });


    actionCell.appendChild(editButton);
    actionCell.appendChild(saveButton);
    actionCell.appendChild(deleteButton);

    return actionCell;
}

function deleteData(value) {
    var url = 'main.php'; // All requests go to main.php

    // Determine the action based on the properties of value
    if (value.hasOwnProperty('id')) {
        value.action = 'delete_food';
    } else if (value.hasOwnProperty('staffID')) {
        value.action = 'delete_staff';
    } else if (value.hasOwnProperty('custID')) {
        value.action = 'delete_customer';
    }

    console.log(value);

    // Send the request if an action was determined
    if (value.hasOwnProperty('action')) {
        fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(value)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(data => {
                fetchData('fetch_food', 'foodTable', true);
                fetchStaff();
                fetchData('fetch_customers', 'userTable', true);
            })
            .catch(error => console.error('Error:', error));
    } else {
        console.error('Error: Invalid data');
    }
}

function createButton(text, className, clickHandler) {
    var button = document.createElement('button');
    button.className = 'btn mt-1 button-width inline-button ' + className;
    button.textContent = text;
    // float left
    button.style.float = 'left';
    button.addEventListener('click', clickHandler);
    return button;
}

function createRow(value, maxLen) {
    var row = document.createElement('tr');

    for (var prop in value) {
        var cell = document.createElement('td');

        if (prop === 'approved') {
            var approveButton = createButton('Approve', 'btn-success', function () {
                value[prop] = 1;
                sendUpdatedData(value);

                // disable the 'Approve' button and change to approved
                approveButton.disabled = true;
                approveButton.innerHTML = 'Approved';

                // remove the 'Deny' button
                var denyButton = cell.querySelector('.btn-danger');
                if (denyButton) {
                    cell.removeChild(denyButton);
                }
            });

            // Create the 'Deny' button only if not approved
            if (value[prop] === 0) {
                var denyButton = createButton('Deny', 'btn-danger', function () {
                    value[prop] = 0;
                    sendUpdatedData(value);
                });
                cell.appendChild(denyButton);
            }

            // If approved, disable the 'Approve' button and update the text
            if (value[prop] === 1) {
                approveButton.disabled = true;
                approveButton.innerHTML = 'Approved';
            }

            // Append the 'Approve' button to the cell
            cell.appendChild(approveButton);
        } else {
            var input = document.createElement('input');
            input.className = 'form-control';
            input.value = value[prop];
            input.disabled = true;
            var factor;
            if (typeof value[prop] === 'number') {
                factor = value[prop] % 1 !== 0 ? 20 :
                    15; // adjust factor based on type and whether it's a decimal
            } else if (value[prop].includes('.')) {
                factor = 12;
            } else {
                factor = 11;
            }
            input.style.width = Math.min(Math.max((maxLen[prop] + 1) * factor, 35), 200) + 'px';
            input.style.textAlign = 'center';
            cell.appendChild(input);
        }

        row.appendChild(cell);
    }

    return row;
}

function clearCustomerOrder() {
    $(document).ready(function () {
        $("#purchaseButton").click(function () {
            $.ajax({
                url: "main.php",
                type: "post",
                data: {
                    action: 'clear_cart'
                },
                success: function (result) {

                    // Show the success message
                    $("#successMessage").show();

                    // Wait for a few seconds, then redirect
                    setTimeout(function () {
                        window.location.href = "index.html";
                    }, 3000);
                }
            });
        });
    });

}

function decrementQuantity(foodID) {
    // Get the input element with the quantity
    var quantityInput = document.getElementById('quantity-' + foodID);

    // Parse the current quantity to an integer and decrement it
    var currentQuantity = parseInt(quantityInput.value, 10);
    // Ensure the quantity does not go below 1
    if (currentQuantity === 1) {
        return;
    }

    currentQuantity -= 1;

    // Update the input field with the new quantity
    quantityInput.value = currentQuantity.toString();
}

function deleteCartItem(foodID) {
    $.ajax({
        url: 'main.php', // replace with your PHP file
        type: 'post',
        data: {
            action: 'delete_cart_item',
            foodID: foodID
        },
        success: function (response) {
            // Check if the item was removed successfully
            if (response === "Item removed from cart successfully.") {
                $('#card-' + foodID).remove();
                fetchData('view_cart', 'carts');
                disableCheckoutButton();
            } else {
                // Handle error
                console.error(response);
            }
        }
    });
}

function disableCheckoutButton() {
    // get all the document ids that start with 'card-'
    var cards = document.querySelectorAll('[id^=card-]');
    if (cards.length === 0) {
        $('#checkout').attr('disabled', 'disabled');
    }

}

function editProfile() {
    var form = document.getElementById('profileForm');
    form.querySelectorAll('input').forEach(function (input) {
        input.disabled = false;
    });

    // change the button text to 'Save'
    var button = document.getElementById('editButton');
    button.textContent = 'Save';
    button.onclick = saveProfile;
    // change the button color to green
    button.classList.remove('btn-primary');
    button.classList.add('btn-success');
    // change type button to submit
    button.type = 'submit';

}

// disable the checkout button if the cart is not empty
function enableCheckoutButton() {
    setTimeout(function () {
        if ($('[id^=card-]').length !== 0) {
            $('#checkout').removeAttr('disabled');
        }
    }, 200);
}

function enableEditing(row) {
    var editableRow = document.querySelector('.editable-row');
    if (editableRow) {
        editableRow.querySelectorAll('input').forEach(function (input) {
            input.disabled = true;
            input.style.backgroundColor = '';
            input.style.border = '';
        });
        editableRow.classList.remove('editable-row');
    }

    row.querySelectorAll('input').forEach(function (input) {

        // if the input is the first column, disable it because it is the id
        if (input === row.querySelector('input')) {
            return;
        }

        input.disabled = false;
        input.style.backgroundColor = 'lightyellow';
        input.style.border = '1px solid black';
    });

    // dont put editable-row class on the first column because it is the id and should not be editable
    row.classList.add('editable-row');
}

function fetchStaff() {
    $.ajax({
        url: 'main.php',
        type: 'POST',
        data: {
            action: 'fetch_staff'
        },
        success: function (data) {
            // Clear the existing content
            $('#accountTable').empty();

            // Loop through each item in the data
            $.each(data, function (index, item) {
                // check if null then just display empty string
                if (item.staffPhoneNo === null) {
                    item.staffPhoneNo = '';
                }

                if (item.staffName === null) {
                    item.staffName = '';
                }

                // Create a new table row for each item
                var row = createRow(item, getMaxLengths(data));
                var actionCell = createActionCell(row, item);

                row.appendChild(actionCell);
                $('#accountTable').append(row);
            });
        }
    });
}


function fetchData(action, elementId, isJson = false) {
    fetch('main.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                action: action
            })
        })
        .catch(error => console.error('Error:', error))
        .then(response => isJson ? response.json() : response.text())
        .then(data => {
            var element = document.getElementById(elementId);
            element.innerHTML = '';

            if (isJson) {
                data.forEach(function (value) {
                    var row = createRow(value, getMaxLengths(data));
                    // check if it is feedback then dont add action cell
                    if (action === 'fetch_feedback') {
                        element.appendChild(row);
                        return;
                    }

                    var actionCell = createActionCell(row, value);

                    row.appendChild(actionCell);
                    element.appendChild(row);
                });
            } else {
                element.innerHTML = data;
            }
        });
}

function fetchFeedback() {
    $.ajax({
        url: 'main.php',
        type: 'POST',
        data: {
            action: 'fetch_feedback'
        },
        success: function (data) { // 'data' is already a JavaScript object
            // Clear the existing content
            $('#feedbackTable').empty();

            // Loop through each item in the data
            $.each(data, function (index, item) {
                // Create a new table row for each item
                var feedbackRow = $('<tr></tr>');

                // Add the customer ID, email, name, issue description, and rating to the row
                feedbackRow.append('<td>' + item.custID + '</td>');
                feedbackRow.append('<td>' + item.email + '</td>');
                feedbackRow.append('<td>' + item.custName + '</td>');
                feedbackRow.append('<td>' + item.issueDescription + '</td>');
                feedbackRow.append('<td>' + item.rating + '</td>');

                // Add the new row to the feedback table
                $('#feedbackTable').append(feedbackRow);
            });
        }
    });
}



function formatCardNumber() {
    this.value = this.value.replace(/[^\d]/g, '').replace(/(.{4})/g, '$1 ').trim();
    if (this.value.length > 19) this.value = this.value.slice(0, 19);
}

function formatCVC() {
    this.value = this.value.replace(/[^\d]/g, '');
    if (this.value.length > 3) this.value = this.value.slice(0, 3);
}

function formatExpiryDate() {
    this.value = this.value.replace(/[^\d]/g, '').replace(/(.{2})/, '$1/');
    if (this.value.length > 7) this.value = this.value.slice(0, 7);
}

function getMaxLengths(data) {
    var maxLen = {};
    data.forEach(function (row) {
        for (var prop in row) {
            // Check if the property is not null or undefined
            if (row[prop] !== null && row[prop] !== undefined) {
                var len = row[prop].toString().length;
                if (!maxLen[prop] || len > maxLen[prop]) {
                    maxLen[prop] = len;
                }
            }
        }
    });
    return maxLen;
}


function incrementQuantity(foodID, maxQuantity) {
    // Get the input element with the quantity
    var quantityInput = document.getElementById('quantity-' + foodID);

    // Parse the current quantity to an integer and increment it
    var currentQuantity = parseInt(quantityInput.value, 10);
    currentQuantity += 1;

    // Ensure the quantity does not exceed the maximum quantity
    if (currentQuantity > maxQuantity) {
        return;
    }


    // Update the input field with the new quantity
    quantityInput.value = currentQuantity.toString();
}

function logout() {
    fetch('main.php?logout=true')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(data => {
            if (data === 'logged out') {
                window.location.href = 'login.html';
            }
        })
        .catch(error => {
            console.error('There has been a problem with your fetch operation:', error);
        });
}

function redirectToLandingPage() {
    window.location.href = 'landingpage.html';
}

function profileForm(user) {
    console.log(user);
    let userName = user.custName || user.staffName || "";
    let userEmail = user.email;
    let userPhone = user.staffPhoneNo || user.custPhoneNo || "";
    let password = user.password;

    var form = document.getElementById('profileForm');

    form.innerHTML = `
    <label for="name">Name</label>
    <input type="text" class="form-control" id="name" disabled>
    <label for="phone">Phone</label>
    <input type="tel" class="form-control" id="phone" disabled>
    <label for="email">Email</label>
    <input type="email" class="form-control" id="email" disabled>
    <label for="password">Password</label>
    <input type="password" class="form-control" id="password" disabled></input>
    `;

    document.getElementById('name').value = userName;
    document.getElementById('email').value = userEmail;
    document.getElementById('phone').value = userPhone;
    document.getElementById('password').value = password;

}

function saveProfile() {
    var form = document.getElementById('profileForm');
    var updatedData = {};


    form.querySelectorAll('input').forEach(function (input) {
        updatedData[input.id] = input.value;
        input.disabled = true;
    });

    $.ajax({
        url: 'main.php',
        type: 'POST',
        data: {
            action: 'update_profile',
            updatedData: updatedData
        },
        error: function (error) {
            console.log(error);
        }
    });

    // change the button text to 'Edit'
    var button = document.getElementById('editButton');
    button.textContent = 'Edit';
    button.onclick = editProfile;
    // change the button color to primary
    button.classList.remove('btn-success');
    button.classList.add('btn-primary');
}

function sendFeedback() {
    var message = document.getElementById('message').value;
    var rating = document.querySelector('input[name="rating"]:checked').value;

    $.ajax({
        url: 'main.php',
        type: 'POST',
        data: {
            action: 'send_feedback',
            message: message,
            rating: rating
        },
    });
}

function sendUpdatedData(updatedData) {

    var url = 'main.php'; // All requests go to main.php

    // Determine the action based on the properties of updatedData
    if (updatedData.hasOwnProperty('id')) {
        updatedData.action = 'update_food';
    } else if (updatedData.hasOwnProperty('staffID')) {
        // if not approved, set approved to 0
        if (!updatedData.hasOwnProperty('approved')) {
            updatedData.approved = 0;
        }
        updatedData.action = 'update_staff';
    } else if (updatedData.hasOwnProperty("custID")) {
        updatedData.action = 'update_cust';
    }

    // Send the request if an action was determined
    if (updatedData.hasOwnProperty('action')) {
        fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(updatedData)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(data => {
                var editableRow = document.querySelector('.editable-row');
                if (editableRow) {
                    editableRow.querySelectorAll('input').forEach(function (input) {
                        input.disabled = true;
                        input.style.backgroundColor = '';
                        input.style.border = '';
                    });
                }
            })
            .catch(error => console.error('Error:', error));
    } else {
        console.error('Error: Invalid data');
    }
}

function sortBy() {
    var priceAlphabet = document.getElementById('priceAlphabet');
    // var sortIcon = document.getElementById('sortIcon');

    // Toggle sort order text and icon (optional)
    if (priceAlphabet.textContent === 'price') {
        priceAlphabet.textContent = 'alphabet';
        // sortIcon.classList.remove('fa-sort');
        // sortIcon.classList.add('fa-sort-up');
    } else {
        priceAlphabet.textContent = 'price';
        // sortIcon.classList.remove('fa-sort-up');
        // sortIcon.classList.add('fa-sort-down');
    }

    // Send AJAX request to update sort order
    fetch('main.php', {
            method: 'POST',
            body: new URLSearchParams({
                sortOrder: priceAlphabet.textContent,
                action: 'view_cart'
            }) // Send sort order as data
        })
        .then(response => response.text())
        .then(data => {
            // Update the view with the sorted data
            document.getElementById('carts').innerHTML = data;
        })
        .catch(error => console.error(error));
}



function validateLogin(event) {
    event.preventDefault();

    fetch('main.php', {
            method: 'POST',
            body: new FormData(event.target)
        })
        .then(response => {
            if (response.redirected) {
                window.location.href = response.url;
            } else {
                return response.json();
            }
        })
        .then(data => {
            if (data && data.error) {
                document.getElementById('error').textContent = data.error;
            }
        })
        .catch(error => console.error('Error:', error));
}

function validateRegister(event) {
    // check if empty

    if (document.getElementById('email').value === '' ||
        document.getElementById('password').value === '') {
        document.getElementById('error').textContent = 'All fields are required';
        return;
    }

    $.ajax({
        url: 'main.php',
        type: 'POST',
        data: new FormData(event.target),
        contentType: false,
        processData: false,
        success: function (response) {
            var trimmedResponse = response.trim();
            if (trimmedResponse === 'Email already registered' ||
                trimmedResponse === 'Connection failed' || trimmedResponse ===
                'Error') {
                document.getElementById('error').textContent = trimmedResponse;
            } else {
                window.location.href =
                    trimmedResponse; // Redirect to the new page
            }
        }
    });
}

function filterReports(reportType) {
    $.ajax({
        url: 'main.php', // replace with your server URL
        type: 'POST',
        data: {
            reportType: reportType,
            action: 'sales_report',
        },
        success: function (data) {
            data = JSON.parse(data);
            if (data.error) {
                alert(data.error);
            } else {
                generatePieChart(data);
            }
        },
        error: function (error) {
            console.log(error);
        }
    });
}

var myPieChart; // declare the chart variable outside the function

function generatePieChart(data) {
    Chart.register(ChartDataLabels);
    // Check if data is in the expected format
    if (!data || !data.labels || !data.datasets || !data.datasets[0] || !data.datasets[0].data || !data.datasets[0].backgroundColor) {
        alert('No data available for the selected period.');
        return;
    }

    var ctx = document.getElementById('salesChart').getContext('2d');

    // If a chart already exists, destroy it
    if (myPieChart) {
        myPieChart.destroy();
    }

    // Create a new chart
    myPieChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.datasets[0].data,
                backgroundColor: data.datasets[0].backgroundColor
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                display: true
            },
            tooltips: {
                enabled: true,
                callbacks: {
                    label: function (tooltipItem, data) {
                        var label = data.labels[tooltipItem.index];
                        var value = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
                        return label + ': RM ' + value.toFixed(2);
                    }
                }
            },
            animation: {
                animateScale: true,
                animateRotate: true
            },
            plugins: {
                datalabels: {
                    formatter: (value, ctx) => {
                        return 'RM ' + value.toFixed(2);
                    },
                    color: '#fff',
                }
            }
        }
    });

}