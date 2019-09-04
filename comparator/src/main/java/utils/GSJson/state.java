package utils.GSJson;

import java.io.File;
import java.util.ArrayList;

public class state {
	private String name;
	private String bin;
	private ArrayList<String> clones;
	private long timeAdded;
	private int id;
	private String url;
	
	public state(String name, String bin, ArrayList<String> clones, long timeAdded) {
		this.name= name;
		this.bin = bin;
		this.clones = clones;
		this.timeAdded= timeAdded;
		this.id = -1;
		this.url = "";
	}

	public state(String name, String bin, ArrayList<String> clones, long timeAdded, int id) {
		this.name= name;
		this.bin = bin;
		this.clones = clones;
		this.timeAdded= timeAdded;
		this.id = id;
		this.url = "";
	}
	public state(String name, String bin, ArrayList<String> clones, long timeAdded, int id, String url) {
		this.name= name;
		this.bin = bin;
		this.clones = clones;
		this.timeAdded= timeAdded;
		this.id = id;
		this.url = url;
	}
	
	public String getName() {
		return name;
	}

	public void setName(String name) {
		this.name = name;
	}

	public String getBin() {
		return bin;
	}

	public void setBin(String bin) {
		this.bin = bin;
	}

	public ArrayList<String> getClones() {
		return clones;
	}

	public void setClones(ArrayList<String> clones) {
		this.clones = clones;
	}

	public long getTimeAdded() {
		return timeAdded;
	}

	public void setTimeAdded(long timeAdded) {
		this.timeAdded = timeAdded;
	}
	public int getId() {
		return id;
	}
	public void setId(int id) {
		this.id = id;
	}
	
	public String getUrl() {
		return url;
	}
	public void setUrl(String url) {
		this.url = url;
	}
}
