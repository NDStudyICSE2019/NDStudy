package comparators.visual;

import java.util.ArrayList;
import java.util.List;

import org.opencv.core.Mat;
import org.opencv.core.MatOfFloat;
import org.opencv.core.MatOfInt;
import org.opencv.imgproc.Imgproc;

import comparators.VisualAbstractComparator;

public class HistogramComparator extends VisualAbstractComparator {

	private String name = "VISUAL-Hyst";
	private Mat page1;
	private Mat page2;

	public HistogramComparator() {
		super();
	}

	public HistogramComparator(Mat p1, Mat p2) {
		super();
		setPage1(p1);
		setPage2(p2);
	}

	@Override
	public double computeDistance() {
		Mat hyst1 = new Mat(), hyst2 = new Mat();

		List<Mat> images = new ArrayList<Mat>();
		images.add(page1);
		Imgproc.calcHist(images, new MatOfInt(0, 1), new Mat(), hyst1, new MatOfInt(256, 256), new MatOfFloat(0.0f, 255.0f, 0.0f, 255.0f));

		images = new ArrayList<Mat>();
		images.add(page2);
		Imgproc.calcHist(images, new MatOfInt(0, 1), new Mat(), hyst2, new MatOfInt(256, 256), new MatOfFloat(0.0f, 255.0f, 0.0f, 255.0f));

		return Imgproc.compareHist(hyst1, hyst2, Imgproc.CV_COMP_CHISQR);

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
