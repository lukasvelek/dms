var type = document.getElementById("input_type");
var lengthElem = document.getElementById("length");
var selectExternalEnum = document.getElementById("select_external_enum");

if(type.value == "select") {
    lengthElem.setAttribute('readonly', true);
    lengthElem.setAttribute('value', '256');
    selectExternalEnum.setAttribute('disabled', true);
} else if(type.value == "boolean") {
    lengthElem.setAttribute('readonly', true);
    lengthElem.setAttribute('value', '2');
    selectExternalEnum.setAttribute('disabled', true);
} else if(type.value == "date") {
    lengthElem.setAttribute('readonly', true);
    lengthElem.setAttribute('value', '10');
    selectExternalEnum.setAttribute('disabled', true);
} else if(type.value == "datetime") {
    lengthElem.setAttribute('readonly', true);
    lengthElem.setAttribute('value', '16');
    selectExternalEnum.setAttribute('disabled', true);
} else if(type.value == "select_external") {
    lengthElem.setAttribute('readonly', true);
    lengthElem.setAttribute('value', '256');
    selectExternalEnum.removeAttribute('disabled');
} else {
    lengthElem.setAttribute('readonly', false);
    lengthElem.setAttribute('value', '');
    selectExternalEnum.setAttribute('disabled', true);
}

type.onchange = function() {
    var value = type.value;

    if(value == "select") {
        lengthElem.setAttribute('readonly', true);
        lengthElem.setAttribute('value', '256');
        selectExternalEnum.setAttribute('disabled', true);
    } else if(value == "boolean") {
        lengthElem.setAttribute('readonly', true);
        lengthElem.setAttribute('value', '2');
        selectExternalEnum.setAttribute('disabled', true);
    } else if(value == "date") {
        lengthElem.setAttribute('readonly', true);
        lengthElem.setAttribute('value', '10');
        selectExternalEnum.setAttribute('disabled', true);
    } else if(value == "datetime") {
        lengthElem.setAttribute('readonly', true);
        lengthElem.setAttribute('value', '16');
        selectExternalEnum.setAttribute('disabled', true);
    } else if(value == "select_external") {
        lengthElem.setAttribute('readonly', true);
        lengthElem.setAttribute('value', '256');
        selectExternalEnum.removeAttribute('disabled');
    } else {
        lengthElem.setAttribute('readonly', false);
        lengthElem.setAttribute('value', '');
        selectExternalEnum.setAttribute('disabled', true);
    }
};