package com.crawljax.examples.ppma;

import com.crawljax.core.configuration.Form;
import com.crawljax.core.configuration.InputSpecification;
import com.crawljax.core.state.Identification;
import com.crawljax.core.state.Identification.How;
import com.crawljax.forms.FormInput;
import com.crawljax.forms.FormInput.InputType;

public class PpmaForms {

	static void login(InputSpecification inputAddressBook) {
		Form loginForm = new Form();

		FormInput username = loginForm.inputField(InputType.TEXT, new Identification(How.id, "LoginForm_username"));
		username.inputValues("admin");

		FormInput password = loginForm.inputField(InputType.TEXT, new Identification(How.id, "LoginForm_password"));
		password.inputValues("admin");

		inputAddressBook.setValuesInForm(loginForm).beforeClickElement("a").underXPath("//*[@id=\"login-form\"]/div[1]/div[2]/a[1]");
	}

}
