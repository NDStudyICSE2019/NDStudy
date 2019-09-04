package comparators.dom;

import java.io.File;
import java.io.IOException;
import java.nio.charset.Charset;

import org.apache.commons.io.FileUtils;
import org.apache.commons.text.similarity.LevenshteinDistance;

import comparators.DomAbstractComparator;
import utils.DomUtils;
import utils.Utils;

public class DOMLevenshteinComparator extends DomAbstractComparator {

	private String name = "DOM-Levenshtein";
	private String page1;
	private String page2;

	public DOMLevenshteinComparator() {
		super();
	}

	public DOMLevenshteinComparator(String p1, String p2) {
		super();
		setPage1(p1);
		setPage2(p2);
	}

	@Override
	public double computeDistance() {

		String dom1 = null, dom2 = null;
		int maxLength = 0;
		
		try {

			dom1 = DomUtils.getDOMWithoutContent(FileUtils.readFileToString(new File(this.getPage1()), Charset.defaultCharset()));
			dom2 = DomUtils.getDOMWithoutContent(FileUtils.readFileToString(new File(this.getPage2()), Charset.defaultCharset()));
			dom1 = DomUtils.getStrippedDom(dom1);
			dom2 = DomUtils.getStrippedDom(dom2);
			maxLength = Math.max(FileUtils.readFileToString(new File(this.getPage1()), Charset.defaultCharset()).length(), 
					FileUtils.readFileToString(new File(this.getPage2()), Charset.defaultCharset()).length());
			
		} catch (IOException e) {
			e.printStackTrace();
		} catch (Exception e) {
			e.printStackTrace();
		}

		//return (double) new LevenshteinDistance().apply(dom1,dom2);
		int lev = Utils.levenshteinDistance(dom1, dom2);

		double returnLev  = (double) lev/maxLength;
		return returnLev;
		
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
