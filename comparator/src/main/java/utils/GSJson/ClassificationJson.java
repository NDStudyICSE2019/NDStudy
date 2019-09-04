package utils.GSJson;

import java.util.ArrayList;
import java.util.HashMap;

public class ClassificationJson {
	
	private HashMap<String, state> states;

	private ArrayList<pair> pairs;
	
	public ClassificationJson(HashMap<String, state> states, ArrayList<pair> pairs) {
		this.states = states;
		this.pairs = pairs;
	}
	
	public HashMap<String, state> getStates() {
		return states;
	}

	public void setStates(HashMap<String, state> states) {
		this.states = states;
	}

	public ArrayList<pair> getPairs() {
		return pairs;
	}

	public void setPairs(ArrayList<pair> pairs) {
		this.pairs = pairs;
	}

}
