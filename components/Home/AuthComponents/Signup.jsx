"use client";
import { z } from "zod";
import React, {
  startTransition,
  useActionState,
  useEffect,
  useState,
} from "react";
import { createSignup } from "@/actions/create-user";
import { ArrowRight, ArrowLeft, Eye, EyeOff, Loader2 } from "lucide-react";
import Link from "next/link";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { toast } from "sonner";

const Signup = () => {

  // const [isPending,setIsPending] = useState(false)

  const [formState, action,isPending] = useActionState(createSignup, {
    errors: {},
  });

 useEffect(() => {
  if (formState?.errors?.success) {
    toast.success("Signup Successful! Your account has been created.");

    // Page reload after 1.5 seconds (to let toast show first)
    setTimeout(() => {
      window.location.reload();
    }, 1500);

  } else if (formState?.errors?.message) {
    toast.error(formState.errors.message || "Signup Failed");
  }
}, [formState]);

  const businessType = [
    "Sole Proprietorship",
    "Partnership",
    "LLC",
    "Corporation",
    "Nonprofit",
    "Other",
  ];

  const countries = [
    { code: "US", name: "United States" },
    { code: "UK", name: "United Kingdom" },
    { code: "CA", name: "Canada" },
    { code: "AU", name: "Australia" },
    { code: "PK", name: "Pakistan" },
    { code: "IN", name: "India" },
    { code: "DE", name: "Germany" },
    { code: "FR", name: "France" },
    { code: "JP", name: "Japan" },
    { code: "BR", name: "Brazil" },
  ];

  const [showPassword, setShowPassword] = useState(false); // to show password fields

  const [businessInfo, setbusinessInfo] = useState(false); // to change form type

  const [errors, setErrors] = useState({}); // to show validation errors

  const [isChecked, setIsChecked] = useState(false);

  const [userData, setUserData] = useState({
    firstName: "",
    lastName: "",
    email: "",
    country: "",
    password: "",
    confirmPassword: "",
  });

  const [businessData, setBusinessData] = useState({
    businessName: "",
    businessType: "",
    businessNo: "",
    businessCountry: "",
  });

  // --- User Schema ---
  const userSchema = z
    .object({
      firstName: z.string().min(1, { message: "First name is required" }),
      lastName: z.string().min(1, { message: "Last name is required" }),
      email: z.string().email({ message: "Invalid email" }),
      password: z
        .string()
        .min(8, { message: "Password must be at least 8 characters" })
        .regex(/[A-Z]/, {
          message: "Password must contain at least one uppercase letter",
        })
        .regex(/[a-z]/, {
          message: "Password must contain at least one lowercase letter",
        })
        .regex(/[0-9]/, {
          message: "Password must contain at least one number",
        })
        .regex(/[^A-Za-z0-9]/, {
          message: "Password must contain at least one special character",
        }),
      confirmPassword: z
        .string()
        .min(1, { message: "Confirm password is required" }),
      country: z.string().min(1, { message: "Country is required" }),
    })
    .refine((data) => data.password === data.confirmPassword, {
      message: "Passwords do not match",
      path: ["confirmPassword"],
    });

  // Business Schema

  const businessSchema = z.object({
    businessName: z.string().min(1, { message: "Business name is required" }),
    businessType: z.string().min(1, { message: "Business type is required" }),
    businessCountry: z
      .string()
      .min(1, { message: "Business country is required" }),
  });

  // Calling server action

  const handleUserDataSubmit = (e) => {
    e.preventDefault();
    const result = userSchema.safeParse(userData);

    if (!result.success) {
      setErrors(result.error.flatten().fieldErrors);

      return;
    }

    setErrors({});

    setbusinessInfo(true);
  };

  const handleFinalSubmit = async (e) => {
    e.preventDefault()
    const result = businessSchema.safeParse(businessData);
    
    if (!result.success) {
      setErrors(result.error.flatten().fieldErrors);
      
      return;
    }
    
    setErrors({});
    
    // setIsPending(true)
    const formData = {
      ...userData,
      ...businessData,
    };
    
    startTransition(() => {
      action(formData);
    });
    // setIsPending(false)
  };

  // ----
  return (
    <div>
      <div className="flex items-center justify-center gap-1">
        <div className="rounded-full h-10  flex justify-center items-center w-10 bg-[#7BC9EE] text-white font-bold">
          <button>1</button>
        </div>
        <div
          className={` h-1 flex justify-center items-center w-10 ${
            businessInfo
              ? "bg-[#7BC9EE] transition-all duration-500"
              : "bg-gray-300"
          } text-white font-bold`}
        ></div>
        <div
          className={`rounded-full h-10  flex justify-center items-center w-10 ${
            businessInfo
              ? "bg-[#7BC9EE] transition-all duration-500"
              : "bg-gray-300"
          } text-white font-bold`}
        >
          <button>2</button>
        </div>
      </div>
      <form onSubmit={handleFinalSubmit}>
        {/* Business info Starts here */}

        <div className={!businessInfo ? "hidden" : "block"}>
          {/* BusinessName */}
          <div className="font-semibold mb-2 mt-5">
            <label htmlFor="businessName">Business Name</label>
          </div>
          <Input
            id="businessName"
            type="text"
            onChange={(e) =>
              setBusinessData({ ...businessData, businessName: e.target.value })
            }
            placeholder="Enter your Business Name"
            className="w-full h-12 px-4 rounded-lg border border-gray-300 bg-gray-100 placeholder:text-[15px]"
          />
          {errors.businessName && (
            <p className="text-red-500 text-xs pt-2 ">
              {errors.businessName}
            </p>
          )}

          {/* BusinessType */}
          <div className="font-semibold mb-2 mt-5">
            <label htmlFor="businessType">Business Type</label>
          </div>
          <Select
            id="businessType"
            onValueChange={(e) =>
              setBusinessData({ ...businessData, businessType: e })
            }
          >
            <SelectTrigger className="w-full cursor-pointer px-4 rounded-lg border border-gray-300 bg-gray-100 placeholder:text-[15px]">
              <SelectValue placeholder="Select your business type" />
            </SelectTrigger>
            <SelectContent>
              <SelectGroup>
                <SelectLabel>Business Type</SelectLabel>
                {businessType.map((e, i) => {
                  return (
                    <SelectItem className={"cursor-pointer"} key={i} value={e}>
                      {e}
                    </SelectItem>
                  );
                })}
              </SelectGroup>
            </SelectContent>
          </Select>
          {errors.businessType && (
            <p  className="text-red-500 text-xs pt-2 ">
              {errors.businessType}
            </p>
          )}

          <div className="font-semibold mb-2 mt-5">
            <label htmlFor="registrationNo">
              Business Registration Number (Optional)
            </label>
          </div>
          <Input
            type="number"
            id="registerationNo"
            onChange={(e) =>
              setBusinessData({ ...businessData, businessNo: e.target.value })
            }
            placeholder="Enter registration number"
            className="w-full h-12 px-4 rounded-lg border border-gray-300 bg-gray-100 placeholder:text-[15px]"
          />
          {/* business Country */}
          <div className="font-semibold mb-2 mt-5">
            <label htmlFor="businessCountry">Business Country</label>
          </div>
          <Select
            id="businessCountry"
            onValueChange={(e) =>
              setBusinessData({ ...businessData, businessCountry: e })
            }
          >
            <SelectTrigger className="w-full cursor-pointer px-4 rounded-lg border border-gray-300 bg-gray-100 placeholder:text-[15px]">
              <SelectValue placeholder="Select your business type" />
            </SelectTrigger>
            <SelectContent>
              <SelectGroup>
                <SelectLabel>Business Country</SelectLabel>
                {countries.map((e, i) => {
                  return (
                    <SelectItem
                      className={"cursor-pointer"}
                      key={i}
                      value={e.code}
                    >
                      {e.name}
                    </SelectItem>
                  );
                })}
              </SelectGroup>
            </SelectContent>
          </Select>
          {errors.businessCountry && (
            <p className="text-red-500 text-xs pt-2 ">
              {errors.businessCountry}
            </p>
          )}

          <div className="mt-7 flex items-center gap-2">
            <input
              type="checkbox"
              id="remember"
              onChange={(e) => {
                setIsChecked(true);
              }}
              className="w-4 h-4 rounded-md border-2 border-gray-400 
               checked:bg-blue-500 checked:border-blue-500 
               focus:ring-2 focus:ring-blue-300 cursor-pointer"
            />
            <label htmlFor="remember" className="text-gray-700 text-sm">
              I agree to the{" "}
              <Link className="font-semibold hover:underline" href={"/"}>
                Terms of Services
              </Link>{" "}
              and{" "}
              <Link className="font-semibold hover:underline" href={"/"}>
                Privacy Policy
              </Link>
            </label>
          </div>

          <div className="my-8 flex items-center gap-5">
            <div>
              {" "}
              <Button
                type="button"
                onClick={() => setbusinessInfo(false)}
                className="cursor-pointer h-12 text-[15px] font-bold "
                variant={"outline"}
              >
                <ArrowLeft /> Back
              </Button>
            </div>
            <div className="flex-1">
              <Button
                disabled={!isChecked || isPending}
                type="submit"
                className=" w-full bg-[#7BC9EE] hover:shadow-xl cursor-pointer h-12 text-[15px] font-bold hover:bg-sky-600"
              >
                {isPending ? (
                  <>
                    <Loader2 className="animate-spin inline-block mr-2" />
                    Please wait
                  </>
                ) : (
                  "Complete Registration"
                )}
              </Button>
            </div>
          </div>
        </div>
      </form>

      {/* Business info ends here */}

      <form onSubmit={handleUserDataSubmit}>
        {/* Personal Information starts here */}

        <div className={!businessInfo ? "block" : "hidden"}>
          {/* First Name */}
          <div className="font-semibold mb-2 mt-5">
            <label htmlFor="firstName">First Name</label>
          </div>
          <Input
            id="firstName"
            type="text"
            placeholder="Enter your first name"
            onChange={(e) =>
              setUserData({ ...userData, firstName: e.target.value })
            }
            className="w-full h-12 px-4 rounded-lg border border-gray-300 bg-gray-100 placeholder:text-[15px]"
          />
          {errors.firstName && (
            <p className="text-red-500 text-xs pt-2 ">{errors.firstName}</p>
          )}
          {/* LastName */}
          <div className="font-semibold mb-2 mt-5">
            <label htmlFor="lastname">Last Name</label>
          </div>
          <Input
            id="lastName"
            onChange={(e) =>
              setUserData({ ...userData, lastName: e.target.value })
            }
            placeholder="Enter your last name"
            className="w-full h-12 px-4 rounded-lg border border-gray-300 bg-gray-100 placeholder:text-[15px]"
          />
          {errors.lastName && (
            <p className="text-red-500 text-xs pt-2 ">
              {errors.lastName}
            </p>
          )}
          {/* Email */}
          <div className="font-semibold mb-2 mt-5">
            <label htmlFor="email">Email Address</label>
          </div>
          <Input
            id="email"
            type="email"
            onChange={(e) =>
              setUserData({ ...userData, email: e.target.value })
            }
            placeholder="Enter your email"
            className="w-full h-12 px-4 rounded-lg border border-gray-300 bg-gray-100 placeholder:text-[15px]"
          />
          {errors.email && (
            <p className="text-red-500 text-xs pt-2 ">
              {errors.email}
            </p>
          )}
          {/* Country */}
          <div className="font-semibold mb-2 mt-5">
            <label htmlFor="country">Country</label>
          </div>
          <Select
            id="country"
            onValueChange={(e) => setUserData({ ...userData, country: e })}
          >
            <SelectTrigger className="w-full cursor-pointer px-4 rounded-lg border border-gray-300 bg-gray-100 placeholder:text-[15px]">
              <SelectValue placeholder="Select your country" />
            </SelectTrigger>
            <SelectContent>
              <SelectGroup>
                <SelectLabel>Country</SelectLabel>
                {countries.map((e, i) => {
                  return (
                    <SelectItem
                      className={"cursor-pointer"}
                      key={i}
                      value={e.code}
                    >
                      {e.name}
                    </SelectItem>
                  );
                })}
              </SelectGroup>
            </SelectContent>
          </Select>
          {errors.country && (
            <p className="text-red-500 text-xs pt-2 ">
              {errors.country}
            </p>
          )}

          {/* Password */}
          <div className="font-semibold mb-2 mt-5">
            <label htmlFor="password">Password</label>
          </div>
          <div className="relative">
            <Input
              id="password"
              type={showPassword ? "text" : "password"}
              onChange={(e) =>
                setUserData({ ...userData, password: e.target.value })
              }
              placeholder="Enter your password"
              className="w-full h-12 px-4 rounded-lg border border-gray-300 bg-gray-100 placeholder:text-[15px] pr-12"
            />

            <button
              type="button"
              onClick={() => setShowPassword(!showPassword)}
              className="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
            >
              {showPassword ? (
                <EyeOff className="cursor-pointer" size={20} />
              ) : (
                <Eye className="cursor-pointer" size={20} />
              )}
            </button>
          </div>
          {errors.password && (
            <p className="text-red-500 text-xs pt-2 ">
              {errors.password[0]}
            </p>
          )}

          {/* repeat password */}
          <div className="font-semibold mb-2 mt-5">
            <label htmlFor="password">Confirm Password</label>
          </div>
          <Input
            id="confirmPassword"
            onChange={(e) =>
              setUserData({ ...userData, confirmPassword: e.target.value })
            }
            type={showPassword ? "text" : "password"}
            placeholder="Enter your password"
            className="w-full h-12 px-4 rounded-lg border border-gray-300 bg-gray-100 placeholder:text-[15px] pr-12"
          />
          {errors.confirmPassword && (
            <p className="text-red-500 text-xs pt-2 ">
              {errors.confirmPassword[0]}
            </p>
          )}

          <div className="my-8 flex items-center gap-5">
            <div className="flex-1">
              <Button
                type="submit"
                className=" w-full bg-[#7BC9EE] hover:shadow-xl cursor-pointer h-12 text-[15px] font-bold hover:bg-sky-600"
              >
                Continue <ArrowRight />
              </Button>
            </div>
          </div>
        </div>
      </form>
    </div>
  );
};

export default Signup;
