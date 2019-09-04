package com.crawljax.examples.addressbook;

import com.crawljax.core.configuration.Form;
import com.crawljax.core.configuration.InputSpecification;
import com.crawljax.core.state.Identification;
import com.crawljax.core.state.Identification.How;
import com.crawljax.forms.FormInput;
import com.crawljax.forms.FormInput.InputType;

public class AddressbookForms {

	static void login(InputSpecification inputAddressBook) {
		Form loginForm = new Form();

		FormInput username = loginForm.inputField(InputType.TEXT, new Identification(How.name, "user"));
		username.inputValues("admin");

		FormInput password = loginForm.inputField(InputType.TEXT, new Identification(How.name, "pass"));
		password.inputValues("admin");

		inputAddressBook.setValuesInForm(loginForm).beforeClickElement("input").withAttribute("value", "Login");
	}

	static void newEntry(InputSpecification inputAddressBook) {
		Form owner = new Form();

		FormInput username = owner.inputField(InputType.TEXT, new Identification(How.name, "firstname"));
		username.inputValues("John");

		FormInput password = owner.inputField(InputType.TEXT, new Identification(How.name, "lastname"));
		password.inputValues("Doe");

		FormInput address = owner.inputField(InputType.TEXTAREA, new Identification(How.name, "address"));
		address.inputValues("1165 The Castings");

		FormInput email = owner.inputField(InputType.TEXT, new Identification(How.name, "email"));
		email.inputValues("jdoe@example.com");

		FormInput bday = owner.inputField(InputType.SELECT, new Identification(How.name, "bday"));
		bday.inputValues("18");

		FormInput bmonth = owner.inputField(InputType.SELECT, new Identification(How.name, "bmonth"));
		bmonth.inputValues("August");

		FormInput byear = owner.inputField(InputType.TEXT, new Identification(How.name, "byear"));
		byear.inputValues("August");

		inputAddressBook.setValuesInForm(owner).beforeClickElement("input").withAttribute("value", "Enter");
	}

	public static void newUser(InputSpecification inputAddressBook) {
		Form newUserForm = new Form();

		FormInput username = newUserForm.inputField(InputType.TEXT, new Identification(How.name, "email"));
		username.inputValues("jdoe@example.com");

		FormInput password = newUserForm.inputField(InputType.TEXT, new Identification(How.name, "password"));
		password.inputValues("123456");

		inputAddressBook.setValuesInForm(newUserForm).beforeClickElement("input").withAttribute("value", "Sign-Up");
	}

}
