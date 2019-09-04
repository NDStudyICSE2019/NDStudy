package comparators.visual;

import java.io.IOException;

import org.opencv.core.Mat;
import org.opencv.core.Scalar;

import comparators.VisualAbstractComparator;

public class SSIMComparator extends VisualAbstractComparator {

	private String name = "VISUAL-SSIM";
	private Mat page1;
	private Mat page2;


	public SSIMComparator() {
		super();
	}

	public SSIMComparator(String p1, String p2) {
		super();
		setPage1(p1);
		setPage2(p2);
	}

	@Override
	public double computeDistance() {

		Scalar mssim;
		try {
			mssim = utils.SSIM.getMSSIM(this.page1, this.page2);
			return mssim.val[0];
		} catch (IOException e) {
			e.printStackTrace();
		}
		return -1;
	}

	@Override
	public String getName() {
		return this.name;
	}

	public Mat getPage1() {
		return this.page1;
	}

	public void setPage1(Mat p1) {
		this.page1 = p1;
	}

	public Mat getPage2() {
		return this.page2;
	}

	public void setPage2(Mat p2) {
		this.page2 = p2;
	}

}
