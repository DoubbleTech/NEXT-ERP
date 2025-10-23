"use server";
import { createConnection } from "@/lib/Database/Database";
import { z } from "zod";
import jwt from "jsonwebtoken";
import bcrypt from "bcrypt";
import { redirect } from "next/navigation";
import { cookies } from "next/headers";

const createSignupSchema = z.object({
  first_name: z.string().trim().min(1, { message: "First name is required" }),
  last_name: z.string().trim().min(1, { message: "Last name is required" }),
  _email: z
    .string()
    .min(1, { message: "Email is required" })
    .max(100)
    .email({ message: "Invalid email format" }),
  _country: z.string({ message: "Country is required" }),
  _password: z
    .string()
    .min(8, { message: "Password must be at least 8 characters" })
    .regex(/[A-Z]/, { message: "Must contain at least one uppercase letter" })
    .regex(/[a-z]/, { message: "Must contain at least one lowercase letter" })
    .regex(/[0-9]/, { message: "Must contain at least one number" })
    .regex(/[^A-Za-z0-9]/, {
      message: "Must contain at least one special character",
    }),
  business_name: z.string({ message: "Business name is required" }),
  business_type: z.string({ message: "Business type is required" }),
  business_country: z.string({ message: "Business country is required" }),
});

export const createSignup = async (prevState, formData) => {
  const {
    firstName,
    lastName,
    email,
    country,
    password,
    confirmPassword,
    businessName,
    businessType,
    businessNo,
    businessCountry,
  } = formData;

  const result = createSignupSchema.safeParse({
    first_name: firstName,
    last_name: lastName,
    _email: email,
    _country: country,
    _password: password,
    business_name: businessName,
    business_type: businessType,
    business_country: businessCountry,
  });

  if (!result.success) {
    return { errors: result.error.flatten().fieldErrors };
  }

  try {
    const db = await createConnection();

    const [existingUser] = await db.query(
      "SELECT id FROM users WHERE email = ?",
      [result.data._email]
    );

    if (existingUser.length > 0) {
      return { errors: { message: "Email already exists" } };
    }

    const encryptedPassword = await bcrypt.hash(result.data._password, 10);

    await db.query(
      `INSERT INTO users 
      (name, firstname, lastname, email, password, country, business_name, business_type, business_no, business_country)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [
        result.data.first_name + " " + result.data.last_name,
        result.data.first_name,
        result.data.last_name,
        result.data._email,
        encryptedPassword,
        result.data._country,
        result.data.business_name,
        result.data.business_type,
        businessNo || null,
        result.data.business_country,
      ]
    );

    return { errors: { success: true, message: "Signup successful" } };
  } catch (error) {
    console.error(error);
    if (error) {
      return { errors: { message: error.message } };
    }
  }
};

const loginSchema = z.object({
  _email: z
    .string()
    .min(1, { message: "Email is required" })
    .email({ message: "Invalid email" }),
  _password: z
    .string()
    .min(8, { message: "Password must be at least 8 characters" }),
});

export const loginUser = async (prevState, formData) => {
  const email = formData.get("email");
  const password = formData.get("password");
  const checkbox = formData.get("checkbox");

  const result = loginSchema.safeParse({
    _email: email,
    _password: password,
  });

  if (!result.success) {
    return { errors: result.error.flatten().fieldErrors };
  }

  try {
    const db = await createConnection();

    const [rows] = await db.query(
      "SELECT id, password FROM users WHERE email = ?",
      [result.data._email]
    );

    if (rows.length === 0) {
      return { errors: { message: "No account found with this email." } };
    }

    const user = rows[0];

    const isMatch = await bcrypt.compare(result.data._password, user.password);

    if (!isMatch) {
      return { errors: { message: "Incorrect email or password." } };
    }

    const MaxAge = checkbox !== null ? 60 * 60 * 24 * 7 : 60 * 60 * 24;

    const token = jwt.sign({ id: user.id, email: user.email }, process.env.JWT_SECRET, {
      expiresIn: checkbox ? "7d" : "1d",
    });

    const cookieStore = await cookies();
    cookieStore.set("auth_token", token, {
      httpOnly: true,
      secure: true,
      path: "/",
      maxAge: MaxAge,
    });

    return { errors: { success: true, message: "Login successful!" } };
  } catch (err) {
    console.error("Login Error:", err.message);
    return { errors: { message: "Something went wrong, try again later." } };
  }
};

export const logoutUser = async () => {
  cookies().delete("auth_token");
  redirect("/");
};
