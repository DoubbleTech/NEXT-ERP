"use client";
import React, { useState } from "react";
import Signin from "./AuthComponents/Signin";
import Signup from "./AuthComponents/Signup";

const Form = () => {
    const [active, setActive] = useState("signin");
  

  const tabClasses = (tab) =>
    `relative px-20 py-3 font-semibold transition-colors duration-300  hover:text-black cursor-pointer
     ${active === tab ? "text-[#7BC9EE]" : "text-gray-600"}
     after:content-[''] after:absolute after:left-0 after:bottom-0 
     after:h-[2px] after:bg-[#7BC9EE] after:transition-all after:duration-300 
     ${active === tab ? "after:w-full" : "after:w-full after:bg-gray-200 "}`;
  return (
    <div>
      <div className="text-center mt-15">
        <div className="text-4xl font-bold text-[#7BC9EE] my-2 ">NEXT ERP</div>
        <div className="text-muted-foreground">
          {active === "signin"? "Sign in to access your dashboard" : "Create a new account"}
        </div>
      </div>
      <div className="flex justify-center  my-8">
        <button
          className={tabClasses("signin")}
          onClick={() => setActive("signin")}
        >
          Sign in
        </button>
        <button
          className={tabClasses("signup")}
          onClick={() => setActive("signup")}
        >
          Sign up
        </button>
      </div>
      {/* form sign in */}
{active === "signin"? <Signin/> : <Signup/>}
      <div>
       
      </div>
    </div>
  );
};

export default Form;
