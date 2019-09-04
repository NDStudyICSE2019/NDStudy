package utils.GSJson;

import java.util.ArrayList;

public class pair {
	private String state1;
	private String state2;
	private ArrayList<String> tags;
	private String comments;
	private int response;
	private int inferred;
	
	public pair(String state1, String state2, int response, int inferred, ArrayList<String> tags, String comments) {
		this.state1 = state1;
		this.state2 = state2;
		this.response = response;
		this.inferred = inferred;
		this.tags = tags;
		this.comments = comments;
	}

	public String getState1() {
		return state1;
	}

	public void setState1(String state1) {
		this.state1 = state1;
	}

	public String getState2() {
		return state2;
	}

	public void setState2(String state2) {
		this.state2 = state2;
	}

	public ArrayList<String> getTags() {
		return tags;
	}

	public void setTags(ArrayList<String> tags) {
		this.tags = tags;
	}

	public String getComments() {
		return comments;
	}

	public void setComments(String comments) {
		this.comments = comments;
	}

	public int getResponse() {
		return response;
	}

	public void setResponse(int response) {
		this.response = response;
	}

	public int getInferred() {
		return inferred;
	}

	public void setInferred(int inferred) {
		this.inferred = inferred;
	}

}
