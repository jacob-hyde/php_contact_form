$('#contactForm').submit(function(event) {
	event.preventDefault();
	var $this = $(this);
	$.post('backend/submitForm.php', $(this).serialize(), function(data){
		$('.error-text, #formResponseMessage').remove();
		$('.has-error').removeClass('has-error');
		if('error' in data && !data.error){ //all good
			$this.prepend('<p class="bg-success" id="formResponseMessage" style="padding: 4px; border-radius: 4px; color: #757575;">Your message was successfully sent!</p>');
		}else if('error' in data && data.error){ //internal error
			$this.prepend('<p class="bg-danger" id="formResponseMessage" style="padding: 4px; border-radius: 4px; color: #757575;">'+data.error+'</p>');
		}else if(typeof data === 'object'){ //validation errors
			for(var input in data){
				var textInput = document.getElementById(input);
				textInput.parentNode.classList.add('has-error');
				console.log($(textInput));
				$(textInput).after('<span class="text-danger error-text" style="margin-top: 10px;">'+data[input]+'</span>');
			}
		}else{
			console.log('Something wrong happened');
		}
	}, 'json');

	return false;
});