<?php
use PHPUnit\Framework\TestCase;
class ContactFormTest extends TestCase{

//----------------------------------------------------------------------------------------------------------------------

	private $passableFormData;

//----------------------------------------------------------------------------------------------------------------------


	public function setUp(){
		$this->passableFormData = ['full_name' => 'Dealer Inspire', 'email' => 'test@test.com', 'phone' => '8888888888', 'message' => 'Donec sed odio dui. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean lacinia bibendum nulla sed consectetur. Cras justo odio, dapibus ac facilisis in, egestas eget quam. Praesent commodo cursus magna, vel scelerisque nisl consectetur et.'];
	}

//----------------------------------------------------------------------------------------------------------------------

	public function testSetData(){
		$form = new ContactFormSubmit();
		$satisfiesRequiredFields = $form->setData($this->passableFormData);
		$this->assertTrue($satisfiesRequiredFields, "Not all required fields are set");
		$this->assertCount(4, $form->getData(), "Incorrect count of submitted form elements");
		$this->assertEquals('Dealer Inspire', $form->getData()['full_name'], "Names do not match");
		$this->assertEquals('test@test.com', $form->getData()['email'], "Emails do not match");
		$this->assertEquals('8888888888', $form->getData()['phone'], "Phone numers do not match");
	}

//----------------------------------------------------------------------------------------------------------------------

	public function testMissingRequiredFormFields(){
		$form = new ContactFormSubmit();
		$formData = $this->passableFormData;
		unset($formData['full_name']);
		$this->assertFalse($form->setData($formData), "Does not stop at required fields");
		$this->assertArrayHasKey('full_name', $form->errors);
	}

//----------------------------------------------------------------------------------------------------------------------

	public function testMissingPhoneFormField(){
		$form = new ContactFormSubmit();
		$formData = $this->passableFormData;
		unset($formData['phone']);
		$this->assertTrue($form->setData($formData), "Non required field phone is being required");
		$this->assertCount(0, $form->errors, "Errors are present");
		$this->assertCount(3, $form->getData(), "Incorrect amount of items in form with missing phone number");
	}

//----------------------------------------------------------------------------------------------------------------------

	public function testInvalidPhoneNumber(){
		$form = new ContactFormSubmit();
		$formData = $this->passableFormData;
		$formData['phone'] = 'not a phone number';
		$form->setData($formData);
		$form->validate();
		$this->assertArrayHasKey('phone', $form->errors, "Errors do not include the phone field when it should fail validation");
		$this->assertEquals('Please enter a valid phone number', $form->errors['phone']);

		$formData['phone'] = 123;
		$form->setData($formData);
		$form->validate();
		$this->assertArrayHasKey('phone', $form->errors, "Errors do not include the phone field when it should fail validation");
		$this->assertEquals('Please enter a valid phone number', $form->errors['phone']);
	}

//----------------------------------------------------------------------------------------------------------------------

	public function testCleanPost(){
		$form = new ContactFormSubmit();
		$formData = $this->passableFormData;
		$formData['message'] = 'Strip Me<script></script>';
		$form->setData($formData);
		$form->validate();
		$this->assertEquals('Strip Me', $form->getData()['message'], "The tags were not stripped from the input");
	}


//----------------------------------------------------------------------------------------------------------------------

	public function testDatabaseConnect(){
		$form = new ContactFormSubmit();
		$this->assertTrue($form->createDBConnection('127.0.0.1', 'root'), "Database Connection not created");
	}

//----------------------------------------------------------------------------------------------------------------------

	public function testSendingEmail(){
		$form = new ContactFormSubmit();
		$satisfiesRequiredFields = $form->setData($this->passableFormData);
		$form->validate();
		$this->assertTrue($form->sendEmail("guy-smiley@example.com"), "Email was not able to be sent!");
	}

//----------------------------------------------------------------------------------------------------------------------

	public function testAddingFormToDatabase()
	{
		$form = new ContactFormSubmit();
		$satisfiesRequiredFields = $form->setData($this->passableFormData);
		$form->validate();
		$form->createDBConnection('127.0.0.1', 'root');
		$form->connectToDatabase();
		$this->assertTrue($form->addContactFormToDatabase(), "Could not add to database!");
		$form->closeDB();
	}

//----------------------------------------------------------------------------------------------------------------------

}

?>