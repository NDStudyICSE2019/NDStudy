package com.crawljax.examples.claroline;

import com.crawljax.core.configuration.Form;
import com.crawljax.core.configuration.InputSpecification;
import com.crawljax.core.state.Identification;
import com.crawljax.core.state.Identification.How;
import com.crawljax.forms.FormInput;
import com.crawljax.forms.FormInput.InputType;

public class ClarolineForms {

	static void login(InputSpecification inputClaroline) {
		Form loginForm = new Form();

		FormInput username = loginForm.inputField(InputType.TEXT, new Identification(How.name, "login"));
		username.inputValues("astocco");

		FormInput password = loginForm.inputField(InputType.PASSWORD, new Identification(How.name, "password"));
		password.inputValues("password");

		inputClaroline.setValuesInForm(loginForm).beforeClickElement("button").withAttribute("type", "submit");
	}

	
	public static void newCourse(InputSpecification inputClaroline) {
		Form newCourseForm = new Form();
		FormInput courseEmail = newCourseForm.inputField(InputType.TEXT, new Identification(How.id, "course_email"));
		courseEmail.inputValues("jdoe@mydomain.net");
		inputClaroline.setValuesInForm(newCourseForm).beforeClickElement("input").withAttribute("value", "Ok");
	}

}
