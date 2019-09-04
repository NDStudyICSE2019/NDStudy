package com.crawljax.examples.dimeshift;

import com.crawljax.core.configuration.Form;
import com.crawljax.core.configuration.InputSpecification;
import com.crawljax.core.state.Identification;
import com.crawljax.core.state.Identification.How;
import com.crawljax.forms.FormInput;
import com.crawljax.forms.FormInput.InputType;

public class DimeShiftForms {
	static void login(InputSpecification inputAddressBook) {
		Form loginForm = new Form();

		FormInput username = loginForm.inputField(InputType.TEXT, new Identification(How.name, "username"));
		username.inputValues("example");

		FormInput password = loginForm.inputField(InputType.PASSWORD, new Identification(How.name, "password"));
		password.inputValues("example123");

		inputAddressBook.setValuesInForm(loginForm).beforeClickElement("input").withAttribute("id", "signin_modal_form_submit");
	}
	
	
	static void register(InputSpecification inputAddressBook) {
		Form registerForm = new Form();

		FormInput username = registerForm.inputField(InputType.TEXT, new Identification(How.id, "input_login"));
		username.inputValues("example");

		FormInput email = registerForm.inputField(InputType.TEXT, new Identification(How.id, "input_email"));
		email.inputValues("example@example.com");
		
		FormInput password = registerForm.inputField(InputType.PASSWORD, new Identification(How.id, "input_password"));
		password.inputValues("example123");

		inputAddressBook.setValuesInForm(registerForm).beforeClickElement("input").withAttribute("id", "registration_modal_form_submit");
	}
}
