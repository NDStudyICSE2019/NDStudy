package com.crawljax.examples.phoenix;

import com.crawljax.core.configuration.Form;
import com.crawljax.core.configuration.InputSpecification;
import com.crawljax.core.state.Identification;
import com.crawljax.core.state.Identification.How;
import com.crawljax.forms.FormInput;
import com.crawljax.forms.FormInput.InputType;

public class PhoenixForms {
	static void login(InputSpecification inputAddressBook) {
		Form loginForm = new Form();

		FormInput username = loginForm.inputField(InputType.TEXT, new Identification(How.id, "user_email"));
		username.inputValues("john@phoenix-trello.com");

		FormInput password = loginForm.inputField(InputType.PASSWORD, new Identification(How.id, "user_password"));
		password.inputValues("12345678");

		inputAddressBook.setValuesInForm(loginForm).beforeClickElement("button").underXPath("//*[@id=\"sign_in_form\"]/button");
	}
	
//	
//	static void register(InputSpecification inputAddressBook) {
//		Form registerForm = new Form();
//
//		FormInput username = registerForm.inputField(InputType.TEXT, new Identification(How.id, "input_login"));
//		username.inputValues("example");
//
//		FormInput email = registerForm.inputField(InputType.TEXT, new Identification(How.id, "input_email"));
//		email.inputValues("example@example.com");
//		
//		FormInput password = registerForm.inputField(InputType.PASSWORD, new Identification(How.id, "input_password"));
//		password.inputValues("example123");
//
//		inputAddressBook.setValuesInForm(registerForm).beforeClickElement("input").withAttribute("id", "registration_modal_form_submit");
//	}
}
