static mut X: i32 = 4;

fn main() {}

unsafe fn test() {
    X = 5;
}
