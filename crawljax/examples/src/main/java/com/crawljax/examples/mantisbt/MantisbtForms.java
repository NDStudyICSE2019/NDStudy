package com.crawljax.examples.mantisbt;

import com.crawljax.core.configuration.Form;
import com.crawljax.core.configuration.InputSpecification;
import com.crawljax.core.state.Identification;
import com.crawljax.core.state.Identification.How;
import com.crawljax.forms.FormInput;
import com.crawljax.forms.FormInput.InputType;

public class MantisbtForms {

	static void login(InputSpecification inputAddressBook) {
		Form loginForm = new Form();

		FormInput username = loginForm.inputField(InputType.TEXT, new Identification(How.name, "username"));
		username.inputValues("administrator");

		FormInput password = loginForm.inputField(InputType.TEXT, new Identification(How.name, "password"));
		password.inputValues("root");

		inputAddressBook.setValuesInForm(loginForm).beforeClickElement("input").withAttribute("value", "Login");
	}

}
