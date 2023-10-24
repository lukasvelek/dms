var type = document.getElementById("input_type");
var lengthElem = document.getElementById("length");

if(type.value == "select") {
    lengthElem.disabled = true;
    lengthElem.value = "256";
} else if(type.value == "boolean") {
    lengthElem.disabled = true;
    lengthElem.value = "2";
} else {
    lengthElem.disabled = false;
    lengthElem.value = "";
}

type.onchange = function() {
    var value = type.value;

    if(value == "select") {
        lengthElem.disabled = true;
        lengthElem.value = "256";
    } else if(type.value == "boolean") {
        lengthElem.disabled = true;
        lengthElem.value = "2";
    } else {
        lengthElem.disabled = false;
        lengthElem.value = "";
    }
};