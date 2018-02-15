function validateEmpty(fld) {
    var error = "";
 
    if (fld.value.length == 0) {
        fld.style.background = 'Yellow'; 
        error = "Required field: "+fld.name+" has not been filled in.\n"
    } else {
        fld.style.background = 'White';
    }
    return error;  
}
function validatePassword(fld) {
    var error = "";
 
    if (fld.value == "") {
        fld.style.background = 'Yellow';
        error = "A password is required.\n";
    } else if ((fld.value.length < 7) || (fld.value.length > 15)) {
        error = "Passwords must be from 7 -to- 15 characters. \n";
        fld.style.background = 'Yellow';
    } else if (illegalChars.test(fld.value)) {
        error = "The password contains illegal characters.\n";
        fld.style.background = 'Yellow';
    } else {
        fld.style.background = 'White';
    }
   return error;
}  
function trim(s)
{
  return s.replace(/^\s+|\s+$/, '');
}
function validateEmail(fld) {
    var error="";
    var tfld = trim(fld.value);                        // value of field with whitespace trimmed off
    var emailFilter = /^[^@]+@[^@.]+\.[^@]*\w\w$/ ;
    var illegalChars= /[\(\)\<\>\,\;\:\\\"\[\]]/ ;
   
    if (fld.value == "") {
        fld.style.background = 'Yellow';
        error = "An email address is required.\n";
    } else if (!emailFilter.test(tfld)) {              //test email for illegal characters
        fld.style.background = 'Yellow';
        error = "Please enter a valid email address.\n";
    } else if (fld.value.match(illegalChars)) {
        fld.style.background = 'Yellow';
        error = "The email address contains illegal characters.\n";
    } else {
        fld.style.background = 'White';
    }
    return error;
}

function validateFile(fld) {
    var error = "";
    if (fld.value == "") {
        fld.style.background = 'Yellow';
        error = "Please select a file to be uploaded.\n";
    } else {
        fld.style.background = 'White';
    }
    return error;
}

function validatePhone(fld) {
    var error = "";
    var stripped = fld.value.replace(/[\(\)\.\-\ ]/g, '');    

   if (fld.value == "") {
        error = "You didn't enter a phone number.\n";
        fld.style.background = 'Yellow';
    } else if (isNaN(parseInt(stripped))) {
        error = "The phone number contains illegal characters.\n";
        fld.style.background = 'Yellow';
    } else if (!(stripped.length == 10)) {
        error = "The phone number is the wrong length. Make sure you included an area code.\n";
        fld.style.background = 'Yellow';
    }
    return error;
}
function validateFiscalYear(fld) {
    var error = "";
    if (fld.value == "") {
        error = "Fiscal year of the form YYYY-YYYY is required.\n";
        fld.style.background = 'Yellow';
    } else if ((fld.value.length != 9)) {
        error = "Fiscal year (YYYY-YYYY) length is invalid.\n";
        fld.style.background = 'Yellow';
    } else {
        var years = fld.value.split("-");
        var year1 = parseInt(years[0]);
        var year2 = parseInt(years[1]);
        if ( (year1+1) != year2 ) {
            error = "Invalid fiscal year string specified.\n";
            fld.style.background = 'Yellow';
        } else if ( year1 < 2012 ) {
            error = "Fiscal year specified is too far in the past.\n";
            fld.style.background = 'Yellow';
        } else if ( year1 > 2030 ) {
            error = "Fiscal year specified is too far in the future.\n";
            fld.style.background = 'Yellow';
        } else {
            fld.style.background = 'White';
        }
    }
    return error;
}
