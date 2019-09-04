package comparators.dom;

import java.io.File;
import java.io.IOException;
import java.nio.charset.Charset;

import org.apache.commons.io.FileUtils;

import comparators.DomAbstractComparator;
import utils.Utils;

public class RTEDComparator extends DomAbstractComparator {

	private String name = "DOM-RTED";
	private String page1;
	private String page2;

	public RTEDComparator() {
		super();
	}

	public RTEDComparator(String p1, String p2) {
		super();
		setPage1(p1);
		setPage2(p2);
	}

	@Override
	public double computeDistance() {

		String dom1 = null, dom2 = null;

		try {
			dom1 = FileUtils.readFileToString(new File(this.getPage1()), Charset.defaultCharset());
			dom2 = FileUtils.readFileToString(new File(this.getPage2()), Charset.defaultCharset());
		} catch (IOException e) {
			e.printStackTrace();
		}

		return Utils.getRobustTreeEditDistance(dom1, dom2);
	}

	@Override
	public String getName() {
		return this.name;
	}

	public String getPage1() {
		return this.page1;
	}

	public void setPage1(String p1) {
		this.page1 = p1;
	}

	public String getPage2() {
		return this.page2;
	}

	public void setPage2(String p2) {
		this.page2 = p2;
	}

}
