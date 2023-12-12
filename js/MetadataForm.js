var type = document.getElementById("input_type");
var lengthElem = document.getElementById("length");

if(type.value == "select") {
    //lengthElem.disabled = true;
    lengthElem.setAttribute('readonly', true);
    //lengthElem.value = "256";
    lengthElem.setAttribute('value', '256');
} else if(type.value == "boolean") {
    //lengthElem.disabled = true;
    lengthElem.setAttribute('readonly', true);
    //lengthElem.value = "2";
    lengthElem.setAttribute('value', '2');
} else if(type.value == "date") {
    //lengthElem.disabled = true;
    lengthElem.setAttribute('readonly', true);
    //lengthElem.value = "10";
    lengthElem.setAttribute('value', '10');
} else if(type.value == "datetime") {
    //lengthElem.disabled = true;
    lengthElem.setAttribute('readonly', true);
    //lengthElem.value = "16";
    lengthElem.setAttribute('value', '16');
} else {
    //lengthElem.disabled = false;
    lengthElem.setAttribute('readonly', false);
    //lengthElem.value = "";
    lengthElem.setAttribute('value', '');
}

type.onchange = function() {
    var value = type.value;

    if(value == "select") {
        //lengthElem.disabled = true;
        lengthElem.setAttribute('readonly', true);
        //lengthElem.value = "256";
        lengthElem.setAttribute('value', '256');
    } else if(type.value == "boolean") {
        //lengthElem.disabled = true;
        lengthElem.setAttribute('readonly', true);
        //lengthElem.value = "2";
        lengthElem.setAttribute('value', '2');
    } else if(type.value == "date") {
        //lengthElem.disabled = true;
        lengthElem.setAttribute('readonly', true);
        //lengthElem.value = "10";
        lengthElem.setAttribute('value', '10');
    } else if(type.value == "datetime") {
        //lengthElem.disabled = true;
        lengthElem.setAttribute('readonly', true);
        //lengthElem.value = "16";
        lengthElem.setAttribute('value', '16');
    } else {
        //lengthElem.disabled = false;
        lengthElem.setAttribute('readonly', false);
        //lengthElem.value = "";
        lengthElem.setAttribute('value', '');
    }
};