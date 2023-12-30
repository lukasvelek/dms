// FILES_KEEP_LENGTH
if(document.getElementById('files_keep_length_text_value') != null && document.getElementById('files_keep_length') != null) {
    var files_keep_length_text_value = document.getElementById('files_keep_length_text_value');
    var files_keep_length_range = document.getElementById('files_keep_length');

    if(files_keep_length_range.value == 1) {
        files_keep_length_text_value.innerHTML = files_keep_length_range.value + " day";
    } else {
        files_keep_length_text_value.innerHTML = files_keep_length_range.value + " days";
    }

    files_keep_length_range.oninput = function() {
        if(files_keep_length_range.value == 1) {
            files_keep_length_text_value.innerHTML = files_keep_length_range.value + " day";
        } else {
            files_keep_length_text_value.innerHTML = files_keep_length_range.value + " days";
        }
    };
}


// PASSWORD_CHANGE_PERIOD
if(document.getElementById('password_change_period_text_value') != null && document.getElementById('password_change_period') != null) {
    var password_change_period_text_value = document.getElementById('password_change_period_text_value');
    var password_change_period_range = document.getElementById('password_change_period');

    if(password_change_period_range.value == 0) {
        password_change_period_text_value.innerHTML = 'Never';
    } else if(password_change_period_range.value == 1) {
        password_change_period_text_value.innerHTML = password_change_period_range.value + " day";
    } else {
        password_change_period_text_value.innerHTML = password_change_period_range.value + " days";
    }

    password_change_period_range.oninput = function() {
        if(password_change_period_range.value == 0) {
            password_change_period_text_value.innerHTML = 'Never';
        } else if(password_change_period_range.value == 1) {
            password_change_period_text_value.innerHTML = password_change_period_range.value + " day";
        } else {
            password_change_period_text_value.innerHTML = password_change_period_range.value + " days";
        }
    };
}


// PASSWORD_CHANGE_FORCE_ADMINISTRATORS
if(document.getElementById('password_change_force_administrators_text_value') != null && document.getElementById('password_change_force_administrators') != null) {
    var password_change_force_administrators_text_value = document.getElementById('password_change_force_administrators_text_value');
    var password_change_force_administrators = document.getElementById('password_change_force_administrators');

    if(password_change_force_administrators.checked == 1) {
        password_change_force_administrators_text_value.innerHTML = 'Force';
        password_change_force_administrators_text_value.style.color = 'red';
    } else {
        password_change_force_administrators_text_value.innerHTML = 'Don\'t force';
        password_change_force_administrators_text_value.style.color = 'green';
    }

    password_change_force_administrators.oninput = function() {
        if(password_change_force_administrators.checked == 1) {
            password_change_force_administrators_text_value.innerHTML = 'Force';
            password_change_force_administrators_text_value.style.color = 'red';
        } else {
            password_change_force_administrators_text_value.innerHTML = 'Don\'t force';
            password_change_force_administrators_text_value.style.color = 'green';
        }
    };
}


// PASSWORD_CHANGE_FORCE
if(document.getElementById('password_change_force_text_value') != null && document.getElementById('password_change_force') != null) {
    var password_change_force_text_value = document.getElementById('password_change_force_text_value');
    var password_change_force = document.getElementById('password_change_force');

    if(password_change_force.checked == 1) {
        password_change_force_text_value.innerHTML = 'Force';
        password_change_force_text_value.style.color = 'red';
    } else {
        password_change_force_text_value.innerHTML = 'Don\'t force';
        password_change_force_text_value.style.color = 'green';
    }

    password_change_force.oninput = function() {
        if(password_change_force.checked == 1) {
            password_change_force_text_value.innerHTML = 'Force';
            password_change_force_text_value.style.color = 'red';
        } else {
            password_change_force_text_value.innerHTML = 'Don\'t force';
            password_change_force_text_value.style.color = 'green';
        }
    };
}


// NOTIFICATION_KEEP_LENGTH
if(document.getElementById('notification_keep_length_text_value') != null && document.getElementById('notification_keep_length') != null) {
    var notification_keep_length_text_value = document.getElementById('notification_keep_length_text_value');
    var notification_keep_length_range = document.getElementById('notification_keep_length');

    if(notification_keep_length_range.value == 1) {
        notification_keep_length_text_value.innerHTML = notification_keep_length_range.value + " day";
    } else if(notification_keep_length_range.value == 0) {
        notification_keep_length_text_value.innerHTML = "Delete seen";
    } else {
        notification_keep_length_text_value.innerHTML = notification_keep_length_range.value + " days";
    }

    notification_keep_length_range.oninput = function() {
        if(notification_keep_length_range.value == 1) {
            notification_keep_length_text_value.innerHTML = notification_keep_length_range.value + " day";
        } else if(notification_keep_length_range.value == 0) {
            notification_keep_length_text_value.innerHTML = "Delete seen";
        } else {
            notification_keep_length_text_value.innerHTML = notification_keep_length_range.value + " days";
        }
    };
}