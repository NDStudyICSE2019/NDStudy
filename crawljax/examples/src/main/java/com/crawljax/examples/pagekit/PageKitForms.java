package com.crawljax.examples.pagekit;

import com.crawljax.core.configuration.Form;
import com.crawljax.core.configuration.InputSpecification;
import com.crawljax.core.state.Identification;
import com.crawljax.core.state.Identification.How;
import com.crawljax.forms.FormInput;
import com.crawljax.forms.FormInput.InputType;

public class PageKitForms {
	static void login(InputSpecification inputAddressBook) {
		Form loginForm = new Form();

		FormInput username = loginForm.inputField(InputType.TEXT, new Identification(How.name, "credentials[username]"));
		username.inputValues("admin");

		FormInput password = loginForm.inputField(InputType.PASSWORD, new Identification(How.name, "credentials[password]"));
		password.inputValues("asdfghjkl123");

		inputAddressBook.setValuesInForm(loginForm).beforeClickElement("button").underXPath("/html[1]/body[1]/div[1]/div[1]/form[1]/div[1]/p[1]/button[1]");
	}
	
	
	static void register(InputSpecification inputAddressBook) {
		Form registerForm = new Form();

		FormInput username = registerForm.inputField(InputType.TEXT, new Identification(How.id, "form-username"));
		username.inputValues("example");

		FormInput name = registerForm.inputField(InputType.TEXT, new Identification(How.id, "form-name"));
		name.inputValues("example123");
		
		FormInput email = registerForm.inputField(InputType.TEXT, new Identification(How.id, "form-email"));
		email.inputValues("example@example.com");
		

		inputAddressBook.setValuesInForm(registerForm).beforeClickElement("button").underXPath("//*[@id=\"user-edit\"]/div[1]/div[2]/button[1]");
	}
	
	static void redirect(InputSpecification inputPageKit) {
		Form redirectForm = new Form();
		FormInput username = redirectForm.inputField(InputType.TEXT, new Identification(How.id, "form-redirect"));
		username.inputValues("");

//		FormInput email = registerForm.inputField(InputType.TEXT, new Identification(How.id, "form-email"));
//		email.inputValues("example@example.com");
//		
//		FormInput name = registerForm.inputField(InputType.TEXT, new Identification(How.id, "form-name"));
//		name.inputValues("example123");

		inputPageKit.setValuesInForm(redirectForm).beforeClickElement("a").underXPath("//*[@id=\"settings\"]/div[4]/div[1]/div[1]/div[1]/a[1]");
	}
}
