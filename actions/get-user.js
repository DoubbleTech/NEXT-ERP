
import { createConnection } from "@/lib/Database/Database";
import { cookies } from "next/headers";
import jwt from "jsonwebtoken";

export const getUser = async () => {
  try {
    const db = await createConnection();

    const cookieSet = await cookies();
    const token = cookieSet.get("auth_token")?.value;
    if (!token) return null;

    const decoded = jwt.verify(token, process.env.JWT_SECRET);
    const userId = decoded?.id;
    if (!userId) return null;
    

    const [rows] = await db.query("SELECT name, email FROM users WHERE id = ?", [userId]);

    if (rows.length > 0) {
      return rows[0];
    } else {
      return null;
    }
  } catch (error) {
    console.log("getUser Error:", error.message);
    return null;
  }
};
