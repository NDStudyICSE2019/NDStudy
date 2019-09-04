package com.crawljax.examples.mrbs;

import com.crawljax.core.configuration.Form;
import com.crawljax.core.configuration.InputSpecification;
import com.crawljax.core.state.Identification;
import com.crawljax.core.state.Identification.How;
import com.crawljax.forms.FormInput;
import com.crawljax.forms.FormInput.InputType;

public class MrbsForms {

	static void login(InputSpecification inputAddressBook) {
		Form loginForm = new Form();

		FormInput username = loginForm.inputField(InputType.TEXT, new Identification(How.id, "NewUserName"));
		username.inputValues("administrator");

		FormInput password = loginForm.inputField(InputType.TEXT, new Identification(How.id, "NewUserPassword"));
		password.inputValues("secret");

		inputAddressBook.setValuesInForm(loginForm).beforeClickElement("input").underXPath("//div[@id=\"logon_submit\"]/input[1]");
	}

}
