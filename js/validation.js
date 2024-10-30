/***************************/
//@Author: Adrian "yEnS" Mato Gondelle & Ivan Guardado Castro
//@website: www.yensdesign.com
//@email: yensamg@gmail.com
//@license: Feel free to use it, but keep this credits please!					
/***************************/

$(document).ready(function(){
	//global vars
	var form = $("#mobilepr_user");
	var fname = $("#user_fname");
	var fnameInfo = $("#fname");
	var lname = $("#user_lname");
	var lnameInfo = $("#lname");
	var email = $("#user_email");
	var emailInfo = $("#email");
	var website = $("#user_website");
	var webInfo = $("#web");
	var code = $("#code");
	var generate = $("#generate");
	
	//On blur
	fname.blur(validateFName);
	lname.blur(validateLName);
	email.blur(validateEmail);
	website.blur(validateWeb);
	code.blur(validateCode);
	//On key press
	fname.keyup(validateFName);
	lname.keyup(validateLName);
	email.keyup(validateEmail);
	website.keyup(validateWeb);

	//On Submitting
	form.submit(function(){
		//if(validateName() & validateEmail() & validatePass1() & validatePass2() & validateMessage())
		if(validateFName() & validateLName() & validateEmail() & validateWeb() | validateCode())
			return true
		else
			return false;
	});
	
	function validateCode(){
		//if it's NOT valid
		if(code.val().length < 1){
			generate.text("Click button to Generate Activation Code");
			generate.addClass("error");
			return false;
		}
		//if it's valid
		else{
			generate.text("");
			generate.removeClass("error");
			return true;
		}
	}
	
	//validation functions
	function validateEmail(){
		//testing regular expression
		var a = $("#user_email").val();
		var filter = /^[a-zA-Z0-9]+[a-zA-Z0-9_.-]+[a-zA-Z0-9_-]+@[a-zA-Z0-9]+[a-zA-Z0-9.-]+[a-zA-Z0-9]+.[a-z]{2,4}$/;
		
		//if it's NOT valid
		if(email.val().length < 1){
			email.addClass("error");
			emailInfo.text("Please Enter Your Email");
			emailInfo.addClass("error");
			return false;
		}else{
			emailInfo.text("");
			email.removeClass("error");
			emailInfo.removeClass("error");
			return true;	
		}
		//if it's valid email
		if(filter.test(a)){
			emailInfo.text("");
			email.removeClass("error");
			emailInfo.removeClass("error");
			return true;
		}
		//if it's NOT valid
		else{
			email.addClass("error");
			emailInfo.text("Please Enter a Valid Email");
			emailInfo.addClass("error");
			return false;
		}
	}
	function validateFName(){
		//if it's NOT valid
		if(fname.val().length < 1){
			fname.addClass("error");
			fnameInfo.text("Please Enter Your First Name");
			fnameInfo.addClass("error");
			return false;
		}
		//if it's valid
		else{
			fnameInfo.text("");
			fname.removeClass("error");
			fnameInfo.removeClass("error");
			return true;
		}
	}
	
	function validateLName(){
		//if it's NOT valid
		if(lname.val().length < 1){
			lname.addClass("error");
			lnameInfo.text("Please Enter Your Last Name");
			lnameInfo.addClass("error");
			return false;
		}
		//if it's valid
		else{
			lnameInfo.text("");
			lname.removeClass("error");
			lnameInfo.removeClass("error");
			return true;
		}
	}
	
	function validateWeb(){
		//if it's NOT valid
		if(website.val().length < 1){
			website.addClass("error");
			webInfo.text("Please Enter Your Website");
			webInfo.addClass("error");
			return false;
		}
		//if it's valid
		else{
			webInfo.text("");
			website.removeClass("error");
			webInfo.removeClass("error");
			return true;
		}
	}
	
});