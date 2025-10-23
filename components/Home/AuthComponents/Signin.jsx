"use client";
import React, { useState, useEffect } from "react";
import { Eye, EyeOff, Loader2 } from "lucide-react";
import Link from "next/link";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { useActionState } from "react";
import { toast } from "sonner";
import { loginUser } from "@/actions/create-user"; 
import { useRouter } from "next/navigation";

const Signin = () => {
  const [showPassword, setShowPassword] = useState(false);
  const router = useRouter();

  const [formState, formAction, isPending] = useActionState(loginUser, {
    errors: {},
  });

  useEffect(() => {
    if (formState?.errors.success) {
      toast.success("Login Successful!");
      router.push("/dashboard");
    } else if (formState?.errors?.message) {
      toast.error(formState.errors.message || "Login Failed");
    }
  }, [formState, router]);

  return (
    <div>
      <form action={formAction}>
        {/* Email */}
        <div className="font-semibold mb-2">
          <label htmlFor="email">Email Address</label>
        </div>
        <Input
          id="email"
          type="email"
          name="email"
          placeholder="Enter your email"
          className="w-full h-12 px-4 rounded-lg border border-gray-300 bg-gray-100 placeholder:text-[15px]"
        />
        {formState?.errors?._email && (
          <p className="text-red-500 text-xs pt-2">
            {formState.errors._email[0]}
          </p>
        )}

        {/* Password */}
        <div className="font-semibold mb-2 mt-8">
          <label htmlFor="password">Password</label>
        </div>
        <div className="relative">
          <Input
            id="password"
            name="password"
            type={showPassword ? "text" : "password"}
            placeholder="Enter your password"
            className="w-full h-12 px-4 rounded-lg border border-gray-300 bg-gray-100 placeholder:text-[15px] pr-12"
          />
          <button
            type="button"
            onClick={() => setShowPassword(!showPassword)}
            className="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
          >
            {showPassword ? <EyeOff size={20} /> : <Eye size={20} />}
          </button>
        </div>
        {formState?.errors?._password && (
          <p className="text-red-500 text-xs pt-2">
            {formState.errors._password}
          </p>
        )}

        {/* Remember Me */}
        <div className="mt-7 flex items-center gap-2">
          <input
            type="checkbox"
            id="remember"
            name="checkbox"
            className="w-4 h-4 rounded-md border-2 border-gray-400 
             checked:bg-blue-500 checked:border-blue-500 
             focus:ring-2 focus:ring-blue-300 cursor-pointer"
          />
          <label htmlFor="remember" className="text-gray-700 text-base">
            Remember me
          </label>
        </div>

        {/* Submit */}
        <div className="mt-5 w-full">
          <Button
            disabled={isPending}
            type="submit"
            className="w-full bg-[#7BC9EE] hover:shadow-xl cursor-pointer h-12 text-[15px] font-bold hover:bg-sky-600"
          >
            {isPending ? (
              <span className="flex items-center gap-2">
                <Loader2 className="animate-spin" size={20} />
                Please wait...
              </span>
            ) : (
              "Sign in"
            )}
          </Button>
          <div className="my-8">
            <Link href={"/"} className="text-[#7BC9EE] font-bold">
              Forgot password?
            </Link>
          </div>
        </div>
      </form>
    </div>
  );
};

export default Signin;
