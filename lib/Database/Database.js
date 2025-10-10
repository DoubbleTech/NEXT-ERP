"use server";
import mysql from "mysql2/promise";


export const createConnection = async () => {
 try {
    const db = await mysql.createConnection({
      host: "localhost",
      user: "root",
      password: "2X6fQt@LK#8LGfF#k",
      database: "next_erp", 
    });
    console.log("✅ Connected!");
    const [rows] = await db.query("SELECT 1");
    console.log(rows);
  } catch (err) {
    console.log("❌ Error:", err.message);
  }
}
