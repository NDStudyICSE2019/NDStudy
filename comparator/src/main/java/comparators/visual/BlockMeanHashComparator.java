package comparators.visual;

import org.opencv.core.Mat;
import org.opencv.img_hash.BlockMeanHash;
import org.opencv.img_hash.PHash;

import comparators.VisualAbstractComparator;

public class BlockMeanHashComparator extends VisualAbstractComparator {

	private String name = "VISUAL-BlockHash";
	private Mat page1;
	private Mat page2;

	public BlockMeanHashComparator(Mat p1, Mat p2) {
		super();
		setPage1(p1);
		setPage2(p2);
	}

	public BlockMeanHashComparator() {
		super();
	}

	@Override
	public double computeDistance() {
		Mat hash1 = new Mat(), hash2 = new Mat();
		BlockMeanHash.create().compute(page1, hash1);
		BlockMeanHash.create().compute(page2, hash2);
		return PHash.create().compare(hash1, hash2);
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
