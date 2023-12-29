var type = document.getElementById("input_type");
var lengthElem = document.getElementById("length");

if(type.value == "select") {
    lengthElem.setAttribute('readonly', true);
    lengthElem.setAttribute('value', '256');
} else if(type.value == "boolean") {
    lengthElem.setAttribute('readonly', true);
    lengthElem.setAttribute('value', '2');
} else if(type.value == "date") {
    lengthElem.setAttribute('readonly', true);
    lengthElem.setAttribute('value', '10');
} else if(type.value == "datetime") {
    lengthElem.setAttribute('readonly', true);
    lengthElem.setAttribute('value', '16');
} else {
    lengthElem.setAttribute('readonly', false);
    lengthElem.setAttribute('value', '');
}

type.onchange = function() {
    var value = type.value;

    if(value == "select") {
        lengthElem.setAttribute('readonly', true);
        lengthElem.setAttribute('value', '256');
    } else if(type.value == "boolean") {
        lengthElem.setAttribute('readonly', true);
        lengthElem.setAttribute('value', '2');
    } else if(type.value == "date") {
        lengthElem.setAttribute('readonly', true);
        lengthElem.setAttribute('value', '10');
    } else if(type.value == "datetime") {
        lengthElem.setAttribute('readonly', true);
        lengthElem.setAttribute('value', '16');
    } else {
        lengthElem.setAttribute('readonly', false);
        lengthElem.setAttribute('value', '');
    }
};