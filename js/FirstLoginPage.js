var pass1 = document.getElementById("password1");
var pass2 = document.getElementById("password2");
                
var msg = document.getElementById("msg");
                
pass1.oninput = function() {
    if(pass2.value != "") {
        if(pass1.value != pass2.value) {
            msg.innerHTML = "Passwords do not match!";
        } else {
            msg.innerHTML = "";
        }
    }
}

pass2.oninput = function() {
    if(pass1.value != "") {
        if(pass1.value != pass2.value) {
            msg.innerHTML = "Passwords do not match!";
        } else {
            msg.innerHTML = "";
        }
    }
}