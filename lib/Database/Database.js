"use server";
import mysql from "mysql2/promise";


export const createConnection = async () => {
 try {
    const db = await mysql.createPool({
      host: process.env.DB_HOST,
      user: process.env.DB_USER,
      password: process.env.DB_PASS,
      database: process.env.DB_NAME, 
    });
    return db
   
  } catch (err) {
    console.log( err.message);
  }
}
